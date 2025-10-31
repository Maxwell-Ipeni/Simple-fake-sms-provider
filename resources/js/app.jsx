import React from 'react';
import { createRoot } from 'react-dom/client';
// import styles (will be processed by Mix/PostCSS)
import '../css/fake-sms.css';

import FakeSms from './components/FakeSms';

const container = document.getElementById('app');
if (container) {
  // React 18 createRoot
  const root = createRoot(container);
  root.render(<FakeSms />);
} else {
  // graceful fallback: inject a minimal notice so the page isn't blank
  const notice = document.createElement('div');
  notice.style.padding = '18px';
  notice.style.color = '#fff';
  notice.textContent = 'UI not mounted: missing #app container';
  document.body.appendChild(notice);
}
