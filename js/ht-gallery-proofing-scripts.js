var HTAjaxFramework = {
    // dummy url - replaced by WordPresses localize scripts function
    ajaxurl: "http://example.com/wordpress/wp-admin/admin-ajax.php",
    ajaxnonce: "abcdef",
};



jQuery(document).ready(function($){

    var changing = false;
    


   function protectionOptions(){
        if ($('input#proofing_protection_option').attr("checked")) {
            // checked
            $('#protection-options').slideDown();
        } else {
            //unchecked
            $('#protection-options').slideUp();
        }
   }


   function proofingOptions(){
        if ($('input#proofing_gallery_option').attr("checked")) {
            // checked
            $('#proofing-options').slideDown();
        } else {
            //unchecked
            $('#proofing-options').slideUp();
        }
   }

   function passwordString(){
        if ($('input#proofing_password_option').attr("checked")) {
            // checked
            $('input#proofing_post_password').prop('disabled', false);
            //manually select the password radio
            $('input#visibility-radio-password').prop('checked', true);
            $('#proofing-password-string').slideDown();
        } else {
            //unchecked            
            $('input#proofing_post_password').prop('disabled', true);
            //manually select the public radio
            $('input#visibility-radio-public').prop('checked', true);
            $('#proofing-password-string').slideUp();
        
            
            
        }
   }

   function userList(){
        if ($('input#proofing_user_restriction_option').attr("checked")) {
            // checked
            $('#protection-user-list').slideDown();
        } else {
            //unchecked
            $('#protection-user-list').slideUp();
        }
   }

   function galleryStart(){
        if ($('input#proofing_time_restriction_start_option').attr("checked")) {
            // checked
            $('#proofing-restrict-start').slideDown();
        } else {
            //unchecked
            $('#proofing-restrict-start').slideUp();
        }
   }

   function galleryExpire(){
        if ($('input#proofing_time_restriction_expire_option').attr("checked")) {
            // checked
            $('#proofing-restrict-expiry').slideDown();
        } else {
            //unchecked
            $('#proofing-restrict-expiry').slideUp();
        }
   }

   function userSearch(){
    console.log("usersearch");
    var searchNeedle = $('input#protection-user-search').val();
    console.log(searchNeedle);
    $('.protection-users').each(function( index ) {
          //get the search haystack
          var searchHaystack = $( this ).attr('data-search');
          var currentElementID = $( this ).attr('value');
          var keep = (searchHaystack.indexOf(searchNeedle) !== -1);
          if(keep==true){
            console.log("keepingID"+currentElementID);
            $('#user-line-'+currentElementID).show();
          } else {
            $('#user-line-'+currentElementID).hide();
          }
        });
   }

   //bind triggers
   $('input#proofing_protection_option').change(function () {
        protectionOptions();
    });
   $('input#proofing_gallery_option').change(function () {
        proofingOptions();
    });
   $('input#proofing_password_option').change(function () {
        passwordString();
    });
   $('input#proofing_user_restriction_option').change(function () {
        userList();
    });
   $('input#proofing_time_restriction_start_option').change(function () {
        galleryStart();
    });
   $('input#proofing_time_restriction_expire_option').change(function () {
        galleryExpire();
    });

   //user search
   $('input#protection-user-search').keyup(function () {
        userSearch();
    });

   $('input#visibility-radio-public').change(function () {
        if ($('input#visibility-radio-public').attr("checked")) {
            $('input#post_password').prop('disabled', true);
            $('input#proofing_password_option').attr("checked", false);
            $('#proofing-password-string').slideUp();
        }
    });

   $('input#visibility-radio-password').change(function () {
        if ($('input#visibility-radio-password').attr("checked")) {
            $('input#post_password').prop('disabled', false);
            $('input#proofing_password_option').attr("checked", true);
            $('#proofing-password-string').slideDown();
        }
    });

   //special case for private post - untick password control
   $('input#visibility-radio-private').change(function () {
        if ($('input#visibility-radio-private').attr("checked")) {
            $('input#proofing_password_option').attr("checked", false);
            $('#proofing-password-string').slideUp();
        }
    });

   //bidirectional password sync
   $("#post_password").bind("keyup paste", function() {
        $("#proofing_post_password").val($(this).val());
    });
   $("#proofing_post_password").bind("keyup paste", function() {
        $("#post_password").val($(this).val());
    });

   //bidirectional comments
   $('input#comment_status').change(function () {
        if ($('input#comment_status').attr("checked")) {
            $('input#proofing_album_comments_option').attr("checked", true);
        } else {
          $('input#proofing_album_comments_option').attr("checked", false);
        }
    });
   $('input#proofing_album_comments_option').change(function () {
        if ($('input#proofing_album_comments_option').attr("checked")) {
            $('input#comment_status').attr("checked", true);
        } else {
          $('input#comment_status').attr("checked", false);
        }
    });
   //initial comments option sync
   if ($('input#proofing_album_comments_option').attr("checked")) {
      $('input#comment_status').attr("checked", true);
    } else {
      $('input#comment_status').attr("checked", false);
    }

   


   //loaded, run scripts
   protectionOptions();
   proofingOptions();
   //no longer required - causes issues with independantly set visibility
   //passwordString();
   userList();
   galleryStart();
   galleryExpire();
});

 