/* globals feather:false */

(function () {
  'use strict'

  feather.replace({ 'aria-hidden': 'true' })

  // Sidebar Toggle Logic
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarToggleMain = document.getElementById('sidebarToggleMain');
  const sidebarMenu = document.getElementById('sidebarMenu');
  const mainContent = document.querySelector('.dashboard-main');

  function toggleSidebar() {
    if (sidebarMenu && mainContent) {
      sidebarMenu.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    }
  }

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
  }

  if (sidebarToggleMain) {
    sidebarToggleMain.addEventListener('click', toggleSidebar);
  }

})()
