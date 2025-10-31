// Minimal JS entry: load bootstrap and the React app entry.
// This avoids pulling Vue into the build (the project uses a React component at resources/js/app.jsx).
require('./bootstrap');

// Ensure the main stylesheet is included by the build
try { require('../css/fake-sms.css'); } catch (e) { }

// Load the React entry which mounts into #app when present
try { require('./app.jsx'); } catch (e) { console.error('Failed to load React entry', e); }
