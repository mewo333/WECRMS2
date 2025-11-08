function updateTime() {
    const timeDiv = document.getElementById('current-time');
    if (timeDiv) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeDiv.textContent = hours + ':' + minutes;
    }
}

updateTime();
setInterval(updateTime, 60000);

const calendar = document.getElementById('calendar');
if (calendar) {
    const currentDate = new Date();
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();
    
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();
    
    let html = '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center;">';
    
    ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => {
        html += `<div style="font-weight: bold; padding: 5px;">${day}</div>`;
    });
    
    for (let i = 0; i < firstDay; i++) {
        html += '<div></div>';
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === currentDate.getDate();
        html += `<div style="padding: 8px; ${isToday ? 'background: #667eea; color: white; border-radius: 50%;' : ''}">${day}</div>`;
    }
    
    html += '</div>';
    calendar.innerHTML = html;
}

// ===== SIDEBAR TOGGLE =====
document.addEventListener('DOMContentLoaded', () => {
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar'); // make sure your sidebar has class="sidebar"

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });
  }

  // ===== DROPDOWN CLOSE ON OUTSIDE CLICK =====
  document.addEventListener('click', (e) => {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
      const menu = dropdown.querySelector('.dropdown-menu');
      if (menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
      }
    }
  });

  // Bootstrap dropdown manual toggle
  const dropdownBtn = document.querySelector('.dropdown-toggle');
  if (dropdownBtn) {
    dropdownBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu = dropdownBtn.nextElementSibling;
      menu.classList.toggle('show');
    });
  }
});
