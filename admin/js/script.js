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
   function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('profileImg');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

document.addEventListener('DOMContentLoaded', function () {
    var sexSelect = document.getElementById('sex-group');
    var firstNameInput = document.getElementById('first_name');

    firstNameInput.addEventListener('focus', function () {
        sexSelect.style.display = 'block'; // Show the select element when input is focused
    });

    // Optional: Hide again when clicking outside
    document.addEventListener('click', function (event) {
        if (!sexSelect.contains(event.target) && !firstNameInput.contains(event.target)) {
            sexSelect.style.display = 'none';
        }
    });
});

