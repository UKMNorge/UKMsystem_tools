jQuery(document).ready(function($){
    var next = 1;
    $(".add-more").click(function(e){
        //console.log('Clicked add-more');
        e.preventDefault();
        var addto = "#field" + next;
        next = next + 1;
        var newIn = '<input autocomplete="off" class="input form-control" id="field' + next + '" name="file' + next + '" type="text">';
        var newInput = $(newIn);
        $(addto).after(newInput);
        //$(addRemove).after(removeButton);
        $("#field" + next).attr('data-source',$(addto).attr('data-source'));
        $("#count").val(next);  
        
          /*  $('.remove-me').click(function(e){
                e.preventDefault();
                var fieldNum = this.id.charAt(this.id.length-1);
                var fieldID = "#field" + fieldNum;
                $(this).remove();
                $(fieldID).remove();
            });*/
    });
    

    
});