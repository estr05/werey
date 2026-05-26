/**
 * Warey — Device Detail Map Controller
 *
 * Extracted from device-detail.blade.php for maintainability.
 * Dependencies: Leaflet 1.9.4, Turf.js 6, Material Icons Round.
 * Config: set window.__WAREY_CONFIG__ before loading this script.
 *
 * window.__WAREY_CONFIG__ = {
 *   lat, lng, alias, activity, selectedDate,
 *   historyUrl, sseUrl, safePlaces: [...]
 * }
 */
(function () {
  'use strict';

  var C = window.__WAREY_CONFIG__ || {};

  // ─────────────────────────────────────────────────────────
  // 1. Mapa Leaflet
  // ─────────────────────────────────────────────────────────

  var map = L.map('map', {
    zoomControl: false,
    attributionControl: false,
  }).setView([C.lat, C.lng], 15);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    attribution:
      '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    opacity: 0.85,
    pane: 'shadowPane',
  }).addTo(map);

  // ─────────────────────────────────────────────────────────
  // 2. Control personalizado — Botón "Centrar en Dispositivo"
  // ─────────────────────────────────────────────────────────

  var CenterControl = L.Control.extend({
    options: { position: 'bottomright' },
    onAdd: function () {
      var container = L.DomUtil.create('div', 'leaflet-bar');
      var btn = L.DomUtil.create('button', '', container);
      btn.title = 'Centrar en dispositivo';
      btn.setAttribute('id', 'btn-center-device');
      btn.innerHTML =
        '<span class="material-icons-round text-xl">my_location</span>';
      btn.className =
        'w-10 h-10 bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md rounded-lg border border-slate-200 dark:border-border-dark shadow-lg flex items-center justify-center hover:text-primary transition-colors text-slate-600 dark:text-slate-300';
      btn.style.cssText = 'cursor:pointer; pointer-events:auto;';
      L.DomEvent.on(btn, 'click', function (e) {
        L.DomEvent.stopPropagation(e);
        var targetLat, targetLng;
        if (liveMarker) {
          var pos = liveMarker.getLatLng();
          targetLat = pos.lat;
          targetLng = pos.lng;
        } else {
          targetLat = C.lat;
          targetLng = C.lng;
        }
        if (targetLat && targetLng) {
          map.flyTo([targetLat, targetLng], 17, {
            animate: true,
            duration: 0.7,
          });
        }
      });
      return container;
    },
  });
  map.addControl(new CenterControl());

  // ─────────────────────────────────────────────────────────
  // 3. Configuración de Confidence
  // ─────────────────────────────────────────────────────────

  var CONFIDENCE = {
    HIGH: { maxAccuracy: 20 },
    MEDIUM: { maxAccuracy: 50 },
  };

  function getConfidenceLevel(p) {
    var acc = p.accuracy || 999;
    if (acc <= CONFIDENCE.HIGH.maxAccuracy) return 'HIGH';
    if (acc <= CONFIDENCE.MEDIUM.maxAccuracy) return 'MEDIUM';
    return 'LOW';
  }

  var baseStyles = {
    WALKING: { color: '#00e5ff' },
    RUNNING: { color: '#ff0055' },
    VEHICLE: { color: '#6CD400' },
    STATIC: { color: '#888888' },
    DEFAULT: { color: '#00e5ff' },
  };

  var confidenceModifiers = {
    HIGH: { weight: 6, opacity: 0.95, dashArray: null },
    MEDIUM: { weight: 4, opacity: 0.65, dashArray: null },
    LOW: { weight: 3, opacity: 0.4, dashArray: '4, 8' },
  };

  // ─────────────────────────────────────────────────────────
  // 4. Variables de estado compartidas
  // ─────────────────────────────────────────────────────────

  var activeRouteLayers = [];
  var activeStopCluster = null;
  var polylineBounds = L.latLngBounds();
  var cleanTelemetry = [];
  var mapLoader = document.getElementById('map-loader');
  var currentBearing = 0;
  var showArrow = false;
  var isDrawingMode = false;
  var creationMarker = null;
  var creationCircle = null;
  var liveMarker = null;
  var autoFollow = true;
  var sseReconnects = 0;
  var sseInstance = null;
  var liveDot = document.getElementById('live-dot');
  var liveText = document.getElementById('live-text');

  // ─────────────────────────────────────────────────────────
  // 5. Funciones de utilidad
  // ─────────────────────────────────────────────────────────

  function showLoader() {
    if (mapLoader) mapLoader.classList.remove('hidden');
  }
  function hideLoader() {
    if (mapLoader) mapLoader.classList.add('hidden');
  }

  function haversineMeters(p1, p2) {
    var R = 6371000;
    var dLat = ((p2.lat - p1.lat) * Math.PI) / 180;
    var dLon = ((p2.lng - p1.lng) * Math.PI) / 180;
    var a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((p1.lat * Math.PI) / 180) *
        Math.cos((p2.lat * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function calculateBearing(p1, p2) {
    var lat1 = (p1.lat * Math.PI) / 180;
    var lat2 = (p2.lat * Math.PI) / 180;
    var dLon = ((p2.lng - p1.lng) * Math.PI) / 180;
    var y = Math.sin(dLon) * Math.cos(lat2);
    var x =
      Math.cos(lat1) * Math.sin(lat2) -
      Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
    return ((Math.atan2(y, x) * 180) / Math.PI + 360) % 360;
  }

  // ─────────────────────────────────────────────────────────
  // 6. Limpieza de capas del mapa
  // ─────────────────────────────────────────────────────────

  function clearMapHistory() {
    activeRouteLayers.forEach(function (l) {
      map.removeLayer(l);
    });
    activeRouteLayers = [];
    if (activeStopCluster) {
      map.removeLayer(activeStopCluster);
      activeStopCluster = null;
    }
  }

  // ─────────────────────────────────────────────────────────
  // 7. Filtro de saltos GPS imposibles (>200 km/h)
  // ─────────────────────────────────────────────────────────

  function isAnomalousJump(p1, p2) {
    var tDiff = (new Date(p2.time) - new Date(p1.time)) / 3600000;
    if (tDiff <= 0) return false;
    var R = 6371;
    var dLat = ((p2.lat - p1.lat) * Math.PI) / 180;
    var dLon = ((p2.lng - p1.lng) * Math.PI) / 180;
    var a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((p1.lat * Math.PI) / 180) *
        Math.cos((p2.lat * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    return (R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))) / tDiff > 200;
  }

  // ─────────────────────────────────────────────────────────
  // 8. Interpolación Catmull-Rom Spline
  // ─────────────────────────────────────────────────────────

  function catmullRomSpline(points, samplesPerSegment) {
    if (points.length < 2) return points;
    samplesPerSegment = samplesPerSegment || 12;
    var pts = [points[0]].concat(points).concat([points[points.length - 1]]);
    var result = [];
    for (var i = 1; i < pts.length - 2; i++) {
      var p0 = pts[i - 1],
        p1 = pts[i],
        p2 = pts[i + 1],
        p3 = pts[i + 2];
      for (var t = 0; t <= 1; t += 1 / samplesPerSegment) {
        var t2 = t * t;
        var t3 = t2 * t;
        var lat =
          0.5 *
          (2 * p1[0] +
            (-p0[0] + p2[0]) * t +
            (2 * p0[0] - 5 * p1[0] + 4 * p2[0] - p3[0]) * t2 +
            (-p0[0] + 3 * p1[0] - 3 * p2[0] + p3[0]) * t3);
        var lng =
          0.5 *
          (2 * p1[1] +
            (-p0[1] + p2[1]) * t +
            (2 * p0[1] - 5 * p1[1] + 4 * p2[1] - p3[1]) * t2 +
            (-p0[1] + 3 * p1[1] - 3 * p2[1] + p3[1]) * t3);
        result.push([lat, lng]);
      }
    }
    result.push(points[points.length - 1]);
    return result;
  }

  // ─────────────────────────────────────────────────────────
  // 9. Renderizado de la ruta (polylines + halos + gaps)
  // ─────────────────────────────────────────────────────────

  function renderRoute(points) {
    var sorted = [].concat(points).sort(function (a, b) {
      return new Date(a.time) - new Date(b.time);
    });

    // Filtrar anomalías
    var clean = [];
    sorted.forEach(function (p, i) {
      if (i > 0 && isAnomalousJump(clean[clean.length - 1], p)) return;
      clean.push(p);
    });
    cleanTelemetry = clean;

    // Segmentar por tipo, confidence y gaps (>5 min)
    var segs = [],
      curSeg = [],
      curType = null,
      curConf = null;
    var prevTime = null;

    clean.forEach(function (p) {
      var t = p.movement_type || 'DEFAULT';
      var conf = getConfidenceLevel(p);
      var time = new Date(p.time).getTime();
      var isGap = prevTime && time - prevTime > 5 * 60000;

      if (t !== curType || conf !== curConf || isGap) {
        if (curSeg.length) {
          segs.push({
            id: 'seg-' + new Date(curSeg[0].time).getTime(),
            type: curType,
            confidence: curConf,
            points: curSeg,
            isGapBefore: isGap,
          });
        }
        curSeg = isGap
          ? [p]
          : curSeg.length
            ? [curSeg[curSeg.length - 1], p]
            : [p];
        curType = t;
        curConf = conf;
      } else {
        curSeg.push(p);
      }
      prevTime = time;
      p._segId = 'seg-' + new Date(curSeg[0].time).getTime();
    });
    if (curSeg.length)
      segs.push({
        id: 'seg-' + new Date(curSeg[0].time).getTime(),
        type: curType,
        confidence: curConf,
        points: curSeg,
        isGapBefore: false,
      });

    // Renderizar polylines y accuracy halos
    polylineBounds = L.latLngBounds();
    segs.forEach(function (seg, index) {
      var base = baseStyles[seg.type] || baseStyles['DEFAULT'];
      var mod = confidenceModifiers[seg.confidence] || confidenceModifiers['LOW'];
      var style = {
        color: base.color,
        weight: mod.weight,
        opacity: mod.opacity,
        dashArray: mod.dashArray,
        lineJoin: 'round',
      };

      var raw = seg.points.map(function (p) {
        return [p.lat, p.lng];
      });
      var pts = raw;
      if (raw.length >= 3 && seg.confidence !== 'LOW') {
        try {
          var line = turf.lineString(
            seg.points.map(function (p) {
              return [p.lng, p.lat];
            })
          );
          var curved = turf.bezierSpline(line, {
            resolution: 10000,
            sharpness: 0.85,
          });
          pts = curved.geometry.coordinates.map(function (c) {
            return [c[1], c[0]];
          });
        } catch (e) {
          console.warn('Turf smoothing failed for segment', e);
          pts = raw;
        }
      }

      // Accuracy halos
      seg.points.forEach(function (p) {
        var acc = p.accuracy || 10;
        if (acc > 15) {
          var halo = L.circle([p.lat, p.lng], {
            radius: acc,
            color: base.color,
            weight: 0,
            fillColor: base.color,
            fillOpacity: 0.1,
          }).addTo(map);
          activeRouteLayers.push(halo);
        }
      });

      if (pts.length > 1) {
        var pl = L.polyline(
          pts,
          Object.assign({}, style, {
            origType: seg.type,
            origConf: seg.confidence,
          })
        ).addTo(map);

        // Metadata del segmento
        var startTime = new Date(seg.points[0].time);
        var endTime = new Date(seg.points[seg.points.length - 1].time);
        var durationMins = Math.max(
          1,
          Math.round((endTime - startTime) / 60000)
        );
        var totalDistance = 0,
          totalSpeed = 0,
          validSpeeds = 0;
        for (var i = 1; i < seg.points.length; i++) {
          totalDistance += haversineMeters(seg.points[i - 1], seg.points[i]);
          if (seg.points[i].speed_kmh) {
            totalSpeed += parseFloat(seg.points[i].speed_kmh);
            validSpeeds++;
          }
        }
        var avgSpeed = validSpeeds > 0 ? (totalSpeed / validSpeeds).toFixed(1) : 0;
        var distStr =
          totalDistance > 1000
            ? (totalDistance / 1000).toFixed(2) + ' km'
            : Math.round(totalDistance) + ' m';
        var confIcon =
          seg.confidence === 'HIGH'
            ? '🟢'
            : seg.confidence === 'MEDIUM'
              ? '🟡'
              : '🔴';

        pl.bindTooltip(
          '<div class="text-slate-900 font-sans p-1 min-w-[160px]">' +
            '<b class="text-xs uppercase text-blue-600 block mb-1">\uD83C\uDFC1 Tramo ' +
            seg.type +
            '</b>' +
            '<div class="text-[10px] space-y-0.5 font-bold text-slate-700">' +
            '<p>' +
            confIcon +
            ' Confianza: ' +
            seg.confidence +
            '</p>' +
            '<p>\u23F1\uFE0F ' +
            startTime.getHours().toString().padStart(2, '0') +
            ':' +
            startTime.getMinutes().toString().padStart(2, '0') +
            ' - ' +
            endTime.getHours().toString().padStart(2, '0') +
            ':' +
            endTime.getMinutes().toString().padStart(2, '0') +
            ' (' +
            durationMins +
            ' min)</p>' +
            '<p>\uD83D\uDCCF Distancia: ' +
            distStr +
            '</p>' +
            '<p>\u26A1 Vel. Promedio: ' +
            avgSpeed +
            ' km/h</p>' +
            '<p class="text-[8px] text-slate-400 font-mono pt-1">Clic para analizar tramo</p>' +
            '</div></div>',
          { sticky: true, className: 'shadow-xl rounded-xl border-none' }
        );

        pl.on('mouseover', function () {
          this.setStyle({ weight: style.weight + 4, opacity: 1 });
        });
        pl.on('mouseout', function () {
          this.setStyle({ weight: style.weight, opacity: style.opacity });
          if (window._activeSegmentId === seg.id) {
            this.setStyle({ color: '#fff', weight: style.weight + 2 });
          }
        });
        pl.on('click', function () {
          map.fitBounds(this.getBounds(), {
            padding: [50, 50],
            animate: true,
            duration: 0.5,
          });
          highlightTimelineSegment(seg.id);
          highlightMapSegment(seg.id, this);
        });

        pl._segId = seg.id;
        activeRouteLayers.push(pl);
        polylineBounds.extend(pl.getBounds());
      } else if (pts.length === 1) {
        polylineBounds.extend(pts[0]);
      }

      // Marcadores de desconexión (gap)
      if (seg.isGapBefore && index > 0 && seg.points.length > 0) {
        var prevSeg = segs[index - 1];
        if (prevSeg.points.length > 0) {
          var p1 = prevSeg.points[prevSeg.points.length - 1];
          var p2 = seg.points[0];

          var m1 = L.marker([p1.lat, p1.lng], {
            icon: L.divIcon({
              className: '',
              html: '<div style="background:rgba(239,68,68,0.25);border:1.5px solid #ef4444;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;color:#ef4444;box-shadow:0 0 8px rgba(239,68,68,0.4);"><span class="material-symbols-outlined" style="font-size:12px;font-weight:bold;">wifi_off</span></div>',
              iconSize: [20, 20],
              iconAnchor: [10, 10],
            }),
          }).addTo(map);
          m1.bindTooltip('Se\u00f1al Perdida: ' + (p1.label_time || ''), {
            className:
              'text-xs text-red-400 bg-slate-950 border-red-500/30 font-bold font-sans',
          });
          activeRouteLayers.push(m1);

          var m2 = L.marker([p2.lat, p2.lng], {
            icon: L.divIcon({
              className: '',
              html: '<div style="background:rgba(16,185,129,0.25);border:1.5px solid #10b981;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;color:#10b981;box-shadow:0 0 8px rgba(16,185,129,0.4);"><span class="material-symbols-outlined" style="font-size:12px;font-weight:bold;">sensors</span></div>',
              iconSize: [20, 20],
              iconAnchor: [10, 10],
            }),
          }).addTo(map);
          m2.bindTooltip(
            'Se\u00f1al Recuperada: ' + (p2.label_time || ''),
            {
              className:
                'text-xs text-emerald-400 bg-slate-950 border-emerald-500/30 font-bold font-sans',
            }
          );
          activeRouteLayers.push(m2);
        }
      }
    });

    if (clean.length > 0 && polylineBounds.isValid()) {
      var lastPoint = clean[clean.length - 1];
      map.flyTo([lastPoint.lat, lastPoint.lng], 17, {
        animate: true,
        duration: 1.0,
      });
    }

    updateHistoryList(clean);
  }

  // ─────────────────────────────────────────────────────────
  // 10. Icono de parada estática
  // ─────────────────────────────────────────────────────────

  function buildStopIcon() {
    return L.divIcon({
      className: '',
      html: '<div style="position:relative;display:flex;align-items:center;justify-content:center;"><div style="background:#ffd600;width:14px;height:14px;border:2.5px solid #131416;border-radius:50%;box-shadow:0 0 12px #ffd600;z-index:2;position:absolute;"></div><div class="pulse-yellow" style="background:#ffd600;width:14px;height:14px;border-radius:50%;position:absolute;"></div><span class="material-symbols-outlined" style="color:#ffd600;font-size:20px;font-weight:bold;position:absolute;top:-20px;text-shadow:0 0 8px #ffd600;">arrow_downward</span></div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
    });
  }

  // ─────────────────────────────────────────────────────────
  // 11. Renderizado de paradas estáticas con clustering
  // ─────────────────────────────────────────────────────────

  function renderStops(points) {
    var stops = [];
    var group = [];

    function processGroup(g) {
      if (!g.length) return;
      var first = g[0],
        last = g[g.length - 1];
      var durationMins = Math.max(
        2,
        Math.round((new Date(last.time) - new Date(first.time)) / 60000)
      );
      var screenMs = 0;
      for (var i = 0; i < g.length - 1; i++) {
        if (g[i].screen_active)
          screenMs += new Date(g[i + 1].time) - new Date(g[i].time);
      }
      stops.push({
        lat: first.lat,
        lng: first.lng,
        restingTime: durationMins,
        screenMinutes: Math.round(screenMs / 60000),
        battery: last.battery ?? last.battery_level ?? '--',
        timeLabel:
          (first.label_time || '') + ' - ' + (last.label_time || ''),
      });
    }

    points.forEach(function (p) {
      if ((p.activity || '').toLowerCase() === 'still') {
        group.push(p);
      } else {
        processGroup(group);
        group = [];
      }
    });
    processGroup(group);

    if (!stops.length) return;

    var cluster = L.markerClusterGroup
      ? L.markerClusterGroup({
          maxClusterRadius: 40,
          showCoverageOnHover: false,
          iconCreateFunction: function (c) {
            return L.divIcon({
              html: '<div style="background:rgba(255,214,0,0.15);border:2px solid #ffd600;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;color:#ffd600;">' + c.getChildCount() + '</div>',
              iconSize: [36, 36],
              iconAnchor: [18, 18],
            });
          },
        })
      : null;

    if (!cluster) return;

    stops.forEach(function (stop) {
      var m = L.marker([stop.lat, stop.lng], { icon: buildStopIcon() });
      m.bindPopup(
        '<div class="text-slate-900 font-sans p-1"><b class="text-xs uppercase text-amber-500 font-bold block mb-1">\uD83D\uDCCD Parada Est\u00e1tica</b><div class="text-[10px] space-y-1 font-semibold text-slate-700"><p>\u23F3 <b>Tiempo reposo:</b> ' +
          stop.restingTime +
          ' min</p><p>\uD83D\uDCF1 <b>Uso de pantalla:</b> ' +
          stop.screenMinutes +
          ' min</p><p>\uD83D\uDD0B <b>Bater\u00eda:</b> ' +
          stop.battery +
          '%</p><p class="text-[8px] text-slate-400 font-mono pt-1">Hora: ' +
          stop.timeLabel +
          '</p></div></div>'
      );
      cluster.addLayer(m);
    });

    map.addLayer(cluster);
    activeStopCluster = cluster;
  }

  // ─────────────────────────────────────────────────────────
  // 12. Lista visual de historial (timeline)
  // ─────────────────────────────────────────────────────────

  function updateHistoryList(points) {
    var container = document.getElementById('history-container');
    if (!container) return;

    container.innerHTML = '';
    if (points.length === 0) {
      container.innerHTML =
        '<div class="text-center py-8 text-slate-600 text-xs italic">Sin transmisiones reportadas para este d\u00eda.</div>';
      return;
    }

    var reversed = [].concat(points).reverse();
    var toShow = reversed.slice(0, 20);

    toShow.forEach(function (p, i) {
      var time = new Date(p.time).getTime();
      var nextP = i < toShow.length - 1 ? toShow[i + 1] : null;
      var isGap = false;
      if (nextP) {
        var nTime = new Date(nextP.time).getTime();
        if (time - nTime > 5 * 60000) isGap = true;
      }

      var isMoving =
        p.activity === 'moving' ||
        ['WALKING', 'RUNNING', 'VEHICLE'].indexOf(p.movement_type) !== -1;
      
      var statusColorClass = isMoving ? 'emerald-500' : 'slate-500';
      
      var speedHtml =
        p.speed_kmh != null
          ? 'Spd: <span class="text-emerald-400">' +
            parseFloat(p.speed_kmh).toFixed(1) +
            ' km/h</span>'
          : 'Spd: --';
          
      var cardinalHtml = p.cardinal
        ? ' \u2022 Dir: ' +
          p.cardinal +
          ' (' +
          Math.round(p.bearing || 0) +
          '\u00b0)'
        : '';
        
      var accHtml = p.accuracy 
        ? 'Acc: <span class="text-primary">' + Math.round(p.accuracy) + 'm</span>' 
        : 'Acc: --';

      var gapHtml = '';
      if (isGap && nextP) {
        var diffMins = Math.round(
          (time - new Date(nextP.time).getTime()) / 60000
        );
        gapHtml =
          '<div class="relative pl-4 pb-6 border-l-2 border-slate-800 last:border-0 last:pb-0 opacity-60">' +
          '<div class="absolute -left-[5px] top-1 w-2 h-2 rounded-full bg-amber-500 ring-4 ring-background-dark"></div>' +
          '<div class="flex items-center gap-2 mt-2 mb-2">' +
          '<div class="h-px bg-slate-800 flex-1"></div>' +
          '<span class="text-[9px] font-mono text-slate-500 uppercase tracking-widest font-bold">Offline / Gap (' +
          diffMins +
          'm)</span>' +
          '<div class="h-px bg-slate-800 flex-1"></div>' +
          '</div></div>';
      }

      var borderClass = isMoving
        ? 'border-emerald-500/30'
        : 'border-slate-800';

      var statusText = (p.movement_type || p.activity || '').toUpperCase();

      container.innerHTML +=
        '<div id="' + p._segId + '-item-' + i + '" data-seg-id="' + p._segId + '" onclick="clickTimelineItem(\'' + p._segId + '\')" ' +
        'class="timeline-item cursor-pointer relative pl-4 pb-6 border-l-2 ' + borderClass + ' last:border-0 last:pb-0 group hover:bg-slate-800/20 transition-all rounded-r-lg p-2 -ml-2">' +
        '<div class="absolute -left-[7px] top-3 w-3 h-3 rounded-full bg-' + statusColorClass + ' ring-4 ring-background-dark group-hover:scale-125 transition-transform"></div>' +
        '<div class="flex justify-between items-start mb-1">' +
        '<span class="text-xs font-bold text-slate-100 font-mono tracking-wider">' + (p.label_time || '') + '</span>' +
        '<span class="text-[9px] uppercase tracking-wider text-' + statusColorClass + ' font-bold bg-' + statusColorClass + '/10 px-2 py-0.5 rounded">' + statusText + '</span>' +
        '</div>' +
        '<p class="text-[10px] text-slate-400 font-mono leading-relaxed group-hover:text-slate-300 transition-colors mt-2">' +
        speedHtml + ' \u2022 Bat: ' + (p.battery || '--') + '%<br>' +
        accHtml + cardinalHtml +
        '</p></div>' +
        gapHtml;
    });
  }

  // ─────────────────────────────────────────────────────────
  // 13. Sincronización Mapa ↔ Timeline
  // ─────────────────────────────────────────────────────────

  window._activeSegmentId = null;

  function highlightTimelineSegment(segId) {
    document.querySelectorAll('.timeline-item').forEach(function (el) {
      el.classList.remove('bg-primary/10', 'rounded-r-lg');
    });
    var target = document.querySelector(
      '.timeline-item[data-seg-id="' + segId + '"]'
    );
    if (target) {
      target.classList.add('bg-primary/10', 'rounded-r-lg');
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function highlightMapSegment(segId, layerInstance) {
    window._activeSegmentId = segId;
    activeRouteLayers.forEach(function (pl) {
      if (pl._segId && pl.options.origConf) {
        var base = baseStyles[pl.options.origType || 'DEFAULT'];
        var mod = confidenceModifiers[pl.options.origConf || 'LOW'];
        pl.setStyle({
          color: base.color,
          weight: mod.weight,
          opacity: mod.opacity,
        });
      }
    });
    if (layerInstance) {
      layerInstance.setStyle({
        color: '#ffffff',
        weight: (layerInstance.options.weight || 3) + 2,
        opacity: 1,
      });
    } else {
      for (var i = 0; i < activeRouteLayers.length; i++) {
        if (activeRouteLayers[i]._segId === segId) {
          var pl = activeRouteLayers[i];
          pl.setStyle({
            color: '#ffffff',
            weight: (pl.options.weight || 3) + 2,
            opacity: 1,
          });
          map.fitBounds(pl.getBounds(), {
            padding: [50, 50],
            animate: true,
            duration: 0.5,
          });
          break;
        }
      }
    }
  }

  window.clickTimelineItem = function (segId) {
    highlightTimelineSegment(segId);
    highlightMapSegment(segId);
  };

  // ─────────────────────────────────────────────────────────
  // 14. Carga del historial desde la API
  // ─────────────────────────────────────────────────────────

  async function loadHistory(date) {
    showLoader();
    clearMapHistory();
    try {
      var res = await fetch(C.historyUrl + '?date=' + encodeURIComponent(date));
      if (!res.ok) throw new Error('HTTP ' + res.status);
      var json = await res.json();
      if (json.success && json.points && json.points.length > 0) {
        renderRoute(json.points);
        renderStops(json.points);
      }
    } catch (err) {
      console.warn('[Warey] Error cargando historial:', err);
    } finally {
      hideLoader();
    }
  }

  // ─────────────────────────────────────────────────────────
  // 15. Dibujar zonas seguras desde config
  // ─────────────────────────────────────────────────────────

  function renderSafePlaces() {
    if (!C.safePlaces || !C.safePlaces.length) return;
    C.safePlaces.forEach(function (place) {
      var safeCircle = L.circle(
        [place.latitude, place.longitude],
        {
          color: '#6CD400',
          fillColor: '#6CD400',
          fillOpacity: 0.15,
          weight: 2,
          dashArray: '4, 6',
          radius: place.radius_meters,
        }
      ).addTo(map);

      safeCircle.bindPopup(
        '<div class="text-slate-900 font-sans p-1">' +
          '<b class="text-xs text-emerald-600 block">\uD83D\uDEE1\uFE0F Per\u00edmetro Seguro</b>' +
          '<p class="text-[10px] text-slate-700 font-bold">Lugar: ' +
          place.name +
          '</p>' +
          '<p class="text-[9px] text-slate-400 font-mono">Radio: ' +
          place.radius_meters +
          'm</p></div>'
      );
    });
  }

  // ─────────────────────────────────────────────────────────
  // 16. Icono de ubicación actual con bearing
  // ─────────────────────────────────────────────────────────

  function buildUnitIcon(bearing, isMoving) {
    var arrowHtml = isMoving
      ? '<div style="position:absolute;top:-11px;left:50%;transform:translateX(-50%);width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-bottom:11px solid #6CD400;filter:drop-shadow(0 0 4px #6CD400);"></div>'
      : '';

    return L.divIcon({
      className: '',
      html:
        '<div style="position:relative;width:28px;height:28px;transform:rotate(' +
        bearing +
        'deg);display:flex;align-items:center;justify-content:center;">' +
        arrowHtml +
        '<div style="background:#6CD400;width:22px;height:22px;border:3.5px solid #ffffff;border-radius:50%;box-shadow:0 0 20px rgba(108,212,0,0.8);position:absolute;"></div>' +
        '<div style="background:#6CD400;width:22px;height:22px;border-radius:50%;animation:pulse 2s infinite;opacity:0.45;position:absolute;"></div>' +
        '</div>',
      iconSize: [28, 28],
      iconAnchor: [14, 14],
    });
  }

  // ─────────────────────────────────────────────────────────
  // 17. Marcador estático inicial (hasta que llegue SSE)
  // ─────────────────────────────────────────────────────────

  if (C.lat && C.lng) {
    var initialIcon = buildUnitIcon(0, false);
    L.marker([C.lat, C.lng], { icon: initialIcon })
      .addTo(map)
      .bindPopup(
        '<b class="text-slate-900">' +
          (C.alias || 'Dispositivo') +
          ' (\u00daltima posici\u00f3n)</b>'
      );
  }

  // ─────────────────────────────────────────────────────────
  // 18. Sistema interactivo de creación de zona segura
  // ─────────────────────────────────────────────────────────

  window.toggleDrawingMode = function () {
    isDrawingMode = !isDrawingMode;
    var btn = document.getElementById('btn-draw');
    var helper = document.getElementById('perimeter-helper');

    if (isDrawingMode) {
      btn.classList.add('bg-[#6CD400]/20', 'border-[#6CD400]');
      var span = btn.querySelector('span');
      if (span) span.innerText = 'Crear Zona Segura (Activo)';
      if (helper) helper.classList.remove('hidden');
      map.getContainer().style.cursor = 'crosshair';
    } else {
      resetDrawingState();
    }
  };

  function resetDrawingState() {
    isDrawingMode = false;
    var btn = document.getElementById('btn-draw');
    var helper = document.getElementById('perimeter-helper');
    var formCard = document.getElementById('safe-place-form-card');

    if (btn) {
      btn.classList.remove('bg-[#6CD400]/20', 'border-[#6CD400]');
      var span = btn.querySelector('span');
      if (span) span.innerText = 'Crear Zona Segura';
    }
    if (helper) helper.classList.add('hidden');
    if (formCard) formCard.classList.add('hidden');
    map.getContainer().style.cursor = '';

    if (creationMarker) map.removeLayer(creationMarker);
    if (creationCircle) map.removeLayer(creationCircle);
    creationMarker = null;
    creationCircle = null;
  }

  window.cancelSafePlace = function () {
    resetDrawingState();
  };

  window.updateCircleRadius = function (val) {
    var el = document.getElementById('radius-value');
    if (el) el.innerText = val + 'm';
    if (creationCircle) {
      creationCircle.setRadius(parseInt(val, 10));
    }
  };

  // Clic en mapa para definir coordenadas de zona segura
  map.on('click', function (e) {
    if (!isDrawingMode) return;

    var clickedLat = e.latlng.lat;
    var clickedLng = e.latlng.lng;

    document.getElementById('form-lat').value = clickedLat;
    document.getElementById('form-lng').value = clickedLng;
    document.getElementById('safe-place-form-card').classList.remove('hidden');

    var radius = parseInt(
      document.getElementById('radius-slider').value,
      10
    );

    if (creationMarker) {
      creationMarker.setLatLng(e.latlng);
      creationCircle.setLatLng(e.latlng);
      creationCircle.setRadius(radius);
    } else {
      creationMarker = L.marker(e.latlng, { draggable: true }).addTo(map);
      creationCircle = L.circle(e.latlng, {
        color: '#6CD400',
        fillColor: '#6CD400',
        fillOpacity: 0.25,
        radius: radius,
      }).addTo(map);

      creationMarker.on('drag', function (evt) {
        var newPos = evt.target.getLatLng();
        document.getElementById('form-lat').value = newPos.lat;
        document.getElementById('form-lng').value = newPos.lng;
        creationCircle.setLatLng(newPos);
      });
    }
  });

  // ─────────────────────────────────────────────────────────
  // 19. Copiar coordenadas al portapapeles
  // ─────────────────────────────────────────────────────────

  window.copyToClipboard = function (text) {
    navigator.clipboard.writeText(text).catch(function () {
      // fallback no-op
    });
  };

  // ─────────────────────────────────────────────────────────
  // 20. Corrección de tamaño de Leaflet tras carga
  // ─────────────────────────────────────────────────────────

  window.addEventListener('load', function () {
    setTimeout(function () {
      map.invalidateSize();
      if (cleanTelemetry.length > 0 && polylineBounds.isValid()) {
        map.fitBounds(polylineBounds, { padding: [50, 50] });
      }
    }, 100);
  });

  // ─────────────────────────────────────────────────────────
  // 21. SSE en tiempo real
  // ─────────────────────────────────────────────────────────

  // Detener auto-follow si el usuario hace pan manualmente
  map.on('dragstart', function () {
    autoFollow = false;
  });

  function animateMarker(marker, newLatLng, durationMs) {
    var start = marker.getLatLng();
    var t0 = performance.now();

    function easeInOut(t) {
      return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
    }

    function step(now) {
      var t = Math.min((now - t0) / durationMs, 1);
      var e = easeInOut(t);
      var lat = start.lat + (newLatLng.lat - start.lat) * e;
      var lng = start.lng + (newLatLng.lng - start.lng) * e;
      marker.setLatLng([lat, lng]);
      if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function setLiveStatus(state) {
    var states = {
      connecting: {
        dot: 'bg-amber-400 animate-pulse',
        text: 'Conectando...',
        color: 'text-amber-400',
      },
      live: {
        dot: 'bg-emerald-500 animate-pulse',
        text: '\u25CF LIVE',
        color: 'text-emerald-400',
      },
      error: { dot: 'bg-red-500', text: 'Sin se\u00f1al', color: 'text-red-400' },
    };
    var s = states[state] || states.error;
    if (liveDot) liveDot.className = 'size-2 rounded-full ' + s.dot;
    if (liveText) {
      liveText.className = 'text-[10px] font-mono ' + s.color;
      liveText.textContent = s.text;
    }
  }

  function handlePosition(data) {
    if (!data.latitude || !data.longitude) return;

    var newLatLng = L.latLng(data.latitude, data.longitude);
    var isMoving = data.activity !== 'still';

    var bearing = currentBearing;
    if (data.bearing != null) {
      bearing = data.bearing;
    } else if (liveMarker) {
      var prev = liveMarker.getLatLng();
      var dist = haversineMeters(
        { lat: prev.lat, lng: prev.lng },
        { lat: data.latitude, lng: data.longitude }
      );
      if (dist > 5) {
        bearing = calculateBearing(
          { lat: prev.lat, lng: prev.lng },
          { lat: data.latitude, lng: data.longitude }
        );
      }
    }
    currentBearing = bearing;

    var newIcon = buildUnitIcon(bearing, isMoving);

    if (!liveMarker) {
      liveMarker = L.marker(newLatLng, { icon: newIcon }).addTo(map);
      liveMarker.bindPopup(
        '<b class="text-slate-900">' +
          (C.alias || 'Dispositivo') +
          ' (En vivo)</b>'
      );
    } else {
      liveMarker.setIcon(newIcon);
      animateMarker(liveMarker, newLatLng, 2000);
    }

    if (autoFollow) {
      map.panTo(newLatLng, { animate: true, duration: 1.0 });
    }

    var speedEl = document.getElementById('live-speed');
    var lastSeenEl = document.getElementById('live-last-seen');

    if (speedEl) {
      speedEl.textContent =
        data.speed_kmh != null
          ? parseFloat(data.speed_kmh).toFixed(1) + ' km/h'
          : '\u2014';
    }
    if (lastSeenEl) {
      lastSeenEl.textContent = data.last_seen || '\u2014';
    }

    setLiveStatus('live');
  }

  function connectDeviceSSE() {
    setLiveStatus('connecting');

    if (sseInstance) sseInstance.close();

    sseInstance = new EventSource(C.sseUrl);

    sseInstance.addEventListener('position', function (e) {
      try {
        sseReconnects = 0;
        handlePosition(JSON.parse(e.data));
      } catch (err) {
        console.warn('[Warey SSE] parse error:', err);
      }
    });

    sseInstance.addEventListener('heartbeat', function () {
      if (liveMarker) setLiveStatus('live');
    });

    sseInstance.onopen = function () {
      sseReconnects = 0;
      setLiveStatus('live');
    };

    sseInstance.onerror = function () {
      sseReconnects++;
      setLiveStatus('error');
      sseInstance.close();
      var delay = Math.min(3000 * Math.pow(2, sseReconnects - 1), 30000);
      setTimeout(connectDeviceSSE, delay);
    };
  }

  // ─────────────────────────────────────────────────────────
  // 22. Inicialización al cargar el DOM
  // ─────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    // Renderizar zonas seguras guardadas
    renderSafePlaces();

    // Cargar historial del día seleccionado
    loadHistory(C.selectedDate);

    // Conectar SSE
    connectDeviceSSE();
  });
})();
