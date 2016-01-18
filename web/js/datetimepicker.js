function isTouchDevice() {
  return !!('ontouchstart' in window);
}

$(document).ready(function(){
  if(!isTouchDevice()){
    $('.datepicker').attr('type', 'text');

      //Mouse powered calendar
    $('.datepicker').datepicker({ showButtonPanel: true });


    $('.datetimepicker').attr('type', 'text');

    //Mouse powered calendar
    $('.datetimepicker').datetimepicker({});

    //Disable the user from manually entering values on the text field
    $('.datetimepicker').bind('keydown',function(e){
      $(this).attr('readonly', 'readonly');
      //Extra fun for working with backspace
      //we don't want the browser to go back in history if backspace is pressed
      if(e.keyCode == 8){
        e.preventDefault();
      }
    });

    //Remove readonly on keyup, so the form doesn't look weird 
    $('.datetimepicker').bind('keyup',function(event){
        if ( event.which == 8 || event.which == 46 ) {
            $(this).val('');
            $(this).blur();
            $(this).datepicker( "hide" );
            $.goToPrev(this);
        }
      $(this).removeAttr('readonly');
    });


  }else{
    //Touch powered calendar
      $('.datepicker').scroller({
          preset: 'date',
          theme: 'android',
          display: 'modal',
          mode: 'scroller'
      });
      $('.datetimepicker').scroller({
      preset: 'datetime',
      theme: 'android',
      display: 'modal',
      mode: 'scroller'
    }); 
  }
});
