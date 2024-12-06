document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.sidebar-menu li.menu-item');

    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove the active class from all menu items
            menuItems.forEach(i => i.classList.remove('active'));
            
            // Add the active class to the clicked item
            this.classList.add('active');
        });
    });
});

document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', () => {
        item.classList.toggle('active');
    });
});