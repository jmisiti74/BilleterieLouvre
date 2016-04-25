function deleteThis(id) {
    $.ajax({
        type: "POST",
        url: "/BilleterieLouvre/web/Ressources/test.php",
        data: {
            id: id
        },
        success: function () {
            location.reload();
        }
    });
}