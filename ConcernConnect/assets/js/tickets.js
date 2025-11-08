// tickets.js - Complete replacement
let searchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-tickets');
    const filterSelect = document.getElementById('filter-status');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadTickets();
            }, 500);
        });
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            loadTickets();
        });
    }
});

function loadTickets() {
    const searchValue = document.getElementById('search-tickets').value;
    const filterValue = document.getElementById('filter-status').value;
    
    fetch('tickets.php?ajax=1&search=' + encodeURIComponent(searchValue) + '&filter=' + encodeURIComponent(filterValue))
        .then(response => response.json())
        .then(tickets => {
            renderTickets(tickets);
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('.tickets-list').innerHTML = '<p class="no-tickets">Error loading tickets.</p>';
        });
}

function renderTickets(tickets) {
    const ticketsList = document.querySelector('.tickets-list');
    
    if (tickets.length === 0) {
        ticketsList.innerHTML = '<p class="no-tickets">No tickets found.</p>';
        return;
    }
    
    let html = '';
    tickets.forEach(function(ticket) {
        const description = ticket.description.length > 100 
            ? ticket.description.substring(0, 100) + '...' 
            : ticket.description;
        
        const date = new Date(ticket.created_at);
        const formattedDate = date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
        
        html += `
            <div class="ticket-item">
                <div class="ticket-info">
                    <h3>
                        <a href="ticket_detail.php?id=${ticket.id}" style="color: #333; text-decoration: none;">
                            ${escapeHtml(ticket.title)}
                        </a>
                    </h3>
                    <p>${escapeHtml(description)}</p>
                    <div class="ticket-meta">
                        <span class="ticket-number">${escapeHtml(ticket.ticket_number)}</span>
                        <span class="badge ${getPriorityClass(ticket.priority)}">${escapeHtml(ticket.priority)}</span>
                        <span class="ticket-category">${escapeHtml(ticket.category_id)}</span>
                        <span class="ticket-date">${formattedDate}</span>
                    </div>
                    <div class="ticket-assigned">Assigned to: ${escapeHtml(ticket.assigned_to)}</div>
                </div>
                <div class="ticket-status">
                    <span class="badge ${getStatusClass(ticket.status)}">${ticket.status.toUpperCase()}</span>
                    <a href="ticket_detail.php?id=${ticket.id}" class="btn btn-sm btn-secondary" style="margin-top: 10px;">View Details</a>
                </div>
            </div>
        `;
    });
    
    ticketsList.innerHTML = html;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function getPriorityClass(priority) {
    const classes = {
        'low': 'bg-success',
        'medium': 'bg-warning',
        'high': 'bg-danger',
        'urgent': 'bg-danger'
    };
    return classes[priority.toLowerCase()] || 'bg-secondary';
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-warning',
        'in progress': 'bg-info',
        'resolved': 'bg-success',
        'closed': 'bg-secondary'
    };
    return classes[status.toLowerCase()] || 'bg-secondary';
}
