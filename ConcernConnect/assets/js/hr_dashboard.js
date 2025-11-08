document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const categoryFilter = document.getElementById('category-filter');
    
    function applyFilters() {
        const search = searchInput.value;
        const status = statusFilter.value;
        const category = categoryFilter.value;
        
        let url = 'hr_dashboard.php?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (status && status !== 'all') url += 'status=' + encodeURIComponent(status) + '&';
        if (category && category !== 'all') url += 'category=' + encodeURIComponent(category);
        
        window.location.href = url;
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }
});

function assignTicket(ticketId, assignTo) {
    if (!assignTo) return;
    
    fetch('hr_assign_ticket.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ticket_id: ticketId,
            assign_to: assignTo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket assigned successfully!');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
