<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1800">
    <title>Tech Board — Officina Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0d1117;
            --surface:  #161b22;
            --border:   #30363d;
            --text:     #e6edf3;
            --muted:    #8b949e;
            --green:    #3fb950;
            --yellow:   #d29922;
            --red:      #f85149;
            --blue:     #58a6ff;
            --orange:   #f0883e;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow: hidden;
        }

        .board-root {
            display: grid;
            grid-template-rows: auto 1fr;
            height: 100vh;
        }

        /* ── Header ─────────────────────────────────────────── */
        .board-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6vw 1.5vw;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .board-logo {
            font-size: 1.4vw;
            font-weight: 700;
            color: var(--blue);
            letter-spacing: 0.05em;
        }

        .board-clock {
            text-align: right;
        }

        .board-clock .time {
            font-size: 2.2vw;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--text);
        }

        .board-clock .date {
            font-size: 0.9vw;
            color: var(--muted);
        }

        /* ── Columns ─────────────────────────────────────────── */
        .board-columns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0;
            overflow: hidden;
        }

        .board-col {
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            overflow: hidden;
        }

        .board-col:last-child { border-right: none; }

        .col-title {
            padding: 0.8vw 1.2vw 0.6vw;
            font-size: 1vw;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .col-title .count {
            margin-left: 0.4em;
            font-size: 0.85em;
            background: var(--border);
            color: var(--text);
            border-radius: 1em;
            padding: 0 0.5em;
        }

        /* ── Scroll container ───────────────────────────────── */
        .col-scroll {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .col-inner {
            padding: 0.8vw;
            display: flex;
            flex-direction: column;
            gap: 0.6vw;
        }

        /* ── Cards ──────────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.5vw;
            padding: 0.8vw 1vw;
        }

        .card-targa {
            font-size: 1.6vw;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: var(--blue);
        }

        .card-modello {
            font-size: 0.85vw;
            color: var(--muted);
            margin-top: 0.1vw;
        }

        .card-meccanico {
            font-size: 1.05vw;
            font-weight: 600;
            color: var(--text);
            margin-top: 0.5vw;
        }

        .card-desc {
            font-size: 0.9vw;
            color: var(--muted);
            margin-top: 0.2vw;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Timer */
        .card-timer {
            margin-top: 0.6vw;
            font-size: 1.3vw;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--green);
        }

        /* Progress bar preventivato/effettivo */
        .card-progress {
            margin-top: 0.5vw;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75vw;
            color: var(--muted);
            margin-bottom: 0.2vw;
        }

        .progress-bar-track {
            height: 0.4vw;
            background: var(--border);
            border-radius: 1em;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 1em;
            transition: width 1s linear;
        }

        .progress-bar-fill.ok   { background: var(--green); }
        .progress-bar-fill.warn { background: var(--yellow); }
        .progress-bar-fill.over { background: var(--red); }

        /* Sezione sospese */
        .card-attesa-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .card-giorni {
            font-size: 0.85vw;
            color: var(--orange);
            font-weight: 600;
        }

        .card-cognome {
            font-size: 0.9vw;
            color: var(--muted);
            margin-top: 0.2vw;
        }

        .card-ricambi {
            margin-top: 0.5vw;
            display: flex;
            flex-wrap: wrap;
            gap: 0.3vw;
        }

        .chip {
            font-size: 0.75vw;
            background: var(--border);
            border-radius: 0.3vw;
            padding: 0.1vw 0.4vw;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 14vw;
        }

        .chip-more {
            color: var(--muted);
            font-style: italic;
        }

        /* Sezione appuntamenti */
        .apt-row {
            display: grid;
            grid-template-columns: 4vw 2.5vw 1fr;
            align-items: center;
            gap: 0.5vw;
            padding: 0.5vw 0.6vw;
            border-radius: 0.4vw;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .apt-time {
            font-size: 1.1vw;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--green);
        }

        .apt-giorno-badge {
            font-size: 0.65vw;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.1vw 0.3vw;
            border-radius: 0.25vw;
            text-align: center;
        }

        .badge-oggi   { background: var(--blue);   color: #0d1117; }
        .badge-domani { background: var(--border);  color: var(--muted); }

        .apt-info {}

        .apt-targa {
            font-size: 1vw;
            font-weight: 700;
            color: var(--blue);
        }

        .apt-desc {
            font-size: 0.8vw;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Empty state */
        .empty-state {
            color: var(--muted);
            font-size: 1vw;
            text-align: center;
            padding: 2vw;
        }
    </style>
</head>
<body>
<div class="board-root">

    {{-- Header con orologio --}}
    <header class="board-header" x-data="{
        time: '',
        date: '',
        tick() {
            const now = new Date();
            this.time = now.toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            this.date = now.toLocaleDateString('it-IT', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});
        }
    }" x-init="tick(); setInterval(() => tick(), 1000)">
        <div class="board-logo">⚙ Officina Hub — Tech Board</div>
        <div class="board-clock">
            <div class="time" x-text="time"></div>
            <div class="date" x-text="date"></div>
        </div>
    </header>

    {{-- Contenuto Livewire --}}
    {{ $slot }}

</div>
</body>
</html>
