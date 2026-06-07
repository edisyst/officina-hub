import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import SignaturePad from 'signature_pad';
import Chart from 'chart.js';

window.Chart = Chart;

// FullCalendar
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import resourceTimeGridPlugin from '@fullcalendar/resource-timegrid';
import itLocale from '@fullcalendar/core/locales/it';

Alpine.plugin(sort);

window.Alpine = Alpine;
window.SignaturePad = SignaturePad;

// Esponi FullCalendar globalmente per i componenti Alpine
window.FullCalendar = {
  Calendar,
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, resourceTimelinePlugin, resourceTimeGridPlugin],
  itLocale,
};

Alpine.start();
