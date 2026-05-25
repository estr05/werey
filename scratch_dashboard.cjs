const fs = require('fs');
const path = 'c:/webapps/laravel/proyectos_personales/warey/resources/views/dashboard.blade.php';

let content = fs.readFileSync(path, 'utf8');

// Replace the <head> completely
const headStart = '<head>';
const headEnd = '</head>';

const newHead = `<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warey | Global Fleet Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              primary: "#06b6d4", // Cyan 500
              "background-light": "#f8fafc",
              "background-dark": "#0a0c10",
              "surface-dark": "#161b22",
              "border-dark": "#30363d",
            },
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              mono: ["JetBrains Mono", "monospace"],
            },
            borderRadius: {
              DEFAULT: "0.5rem",
            },
          },
        },
      };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        /* Efecto de brillo para el botón de detalles */
        .btn-manage:hover {
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
        }
    </style>
</head>`;

let parts = content.split(headEnd);
if(parts.length > 1) {
    let beforeHeadEnd = parts[0];
    let beforeHeadStart = beforeHeadEnd.split(headStart)[0];
    content = beforeHeadStart + newHead + parts[1];
}

// Global replaces for colors and specific classes
content = content.replace(/bg-\[#131416\]/g, 'bg-background-light dark:bg-background-dark');
content = content.replace(/bg-\[#1c1e21\]/g, 'bg-white dark:bg-surface-dark');
content = content.replace(/border-slate-800/g, 'border-slate-200 dark:border-border-dark');
content = content.replace(/border-\[#005d70\]\/50/g, 'border-primary/50');
content = content.replace(/text-\[#005d70\]/g, 'text-primary');
content = content.replace(/bg-\[#005d70\]\/10/g, 'bg-primary/10');
content = content.replace(/bg-\[#005d70\]/g, 'bg-primary');
content = content.replace(/hover:bg-\[#007b94\]/g, 'hover:bg-cyan-600');
content = content.replace(/text-\[#6CD400\]/g, 'text-emerald-500');
content = content.replace(/bg-\[#6CD400\]\/20/g, 'bg-emerald-500/20');
content = content.replace(/bg-\[#6CD400\]/g, 'bg-emerald-500');

// Typography
content = content.replace(/font-family: 'Epilogue'/g, "font-family: 'Inter'");
content = content.replace(/font-mono/g, 'mono');
content = content.replace(/text-slate-100/g, 'text-slate-900 dark:text-slate-100');
content = content.replace(/text-white/g, 'text-slate-900 dark:text-white');

// Header Text & specific element adjustments
content = content.replace(/text-\[#8dc3ce\]/g, 'text-primary');
content = content.replace(/bg-slate-800/g, 'bg-slate-100 dark:bg-slate-800');
content = content.replace(/hover:bg-slate-700/g, 'hover:bg-slate-200 dark:hover:bg-slate-700');
content = content.replace(/text-slate-300/g, 'text-slate-600 dark:text-slate-300');
content = content.replace(/text-slate-400/g, 'text-slate-500 dark:text-slate-400');
content = content.replace(/border-slate-700/g, 'border-slate-200 dark:border-border-dark');
content = content.replace(/border-slate-800/g, 'border-slate-200 dark:border-border-dark');

// Fix JavaScript strings containing the same exact colors
content = content.replace(/bg-\[#1c1e21\]/g, 'bg-white dark:bg-surface-dark'); // Just in case it was in JS template literal too

// Write back
fs.writeFileSync(path, content, 'utf8');
console.log('Aesthetic updates applied to dashboard.blade.php');
