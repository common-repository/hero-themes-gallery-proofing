var HTAjaxFramework = {
    // dummy url - replaced by WordPresses localize scripts function
    ajaxurl: "http://example.com/wordpress/wp-admin/admin-ajax.php",
    ajaxnonce: "abcdef",
};



jQuery(document).ready(function($){

  //voting actions, enable ajax buttons for voting
  function votingActions(){
    $('a.gallery-proofing-vote-action.canvote').each(function( index ) {
            var voteActionAnchor = $(this);
            var targetAttachmentID = voteActionAnchor.attr('data-attachment-id');
            var targetGalleryID = voteActionAnchor.attr('data-gallery-id');
            var voteActionParam = voteActionAnchor.attr('data-action');
            var voteClickedID = voteActionAnchor.attr('id');
            voteActionAnchor.click(function(event){
                event.preventDefault();
                var data = {
                  action: 'ht_proofing_vote',
                  attachment_id: targetAttachmentID,
                  gallery_id: targetGalleryID,
                  vote_direction: voteActionParam,
                  ajax_nonce: framework.ajaxnonce
                };
                $.post(framework.ajaxurl, data, function(response) {
                  if(response!=''){
                    //replace the voting box with response
                    $('#gallery-proofing-voting-box-'+targetAttachmentID).replaceWith(response);
                    englargeElement(voteClickedID);
                    votingActions();
                  }
                });
            });
    });

    //disable the voting buttons if they can't vote
    $('a.gallery-proofing-vote-action.cantvote').click(function(e){
        e.preventDefault();
        alert(framework.cantvotetext);
      });
    }

  //englarge element animation
  function englargeElement(elementID){
    eleToEnlarge = $('#' + elementID);
    if(eleToEnlarge.length>0){
      var fontSize = eleToEnlarge.css( 'font-size' );
      var oldSize =parseFloat(fontSize);
      var newSize = oldSize  * 2;

      eleToEnlarge.animate({ fontSize: newSize}, 200, function(){
        eleToEnlarge.animate({ fontSize: oldSize}, 200);
      });
    }
  }

  //add a return id to the comments to snap back to
  $('.proofing-comments form').each(function( index ) {
          var galleryPost = $('#single-gallery-proofing').attr('data-ht-gallery-post');
          var galleryPostInput = '<input type="hidden" name="ht_proofing_return_post_id" value="' + galleryPost + '">';
          $(this).append(galleryPostInput);
  });

  var currentCommentStatusAreaID = null;

  //ajaxify comment forms
  $('.comment-form').ajaxForm({
            url: framework.ajaxurl+"?ajaxcomments",
            data: {
                // additional data to be included along with the form fields
                action : 'ht_proofing_comments',
                ajax_nonce: framework.ajaxnonce
            },
            dataType: 'json',
            beforeSubmit: function(formData, jqForm, options) {
               //prepend a text area to the form
               var formID = jqForm.attr('id');
               currentCommentStatusAreaID = '#' + formID + '-comment-status';
               var commentStatus = $('#' + formID + '-comment-status');
               if(commentStatus.length>0){
                commentStatus.remove();
               }
               jqForm.prepend('<div id="' + formID + '-comment-status" class="comment-status" >' + framework.postingcomment + '</div>');
            },
            error : function(data) {
              var statusArea = $(currentCommentStatusAreaID);
              if(statusArea.length>0){
                    statusArea.html(data.responseText);
                  }   
            },
            success : function(data) {
                var attachmentID = data.attachmentID;
                if(data.state == 'success'){
                  //handle succes
                  var newCommentList = data.html;
                  var newCommentID = data.newCommentID;
                  $('ul#comment-area-'+attachmentID).replaceWith(newCommentList);
                  newComment = $('div-comment-'+newCommentID);
                  if(newComment.length>0){
                    //scroll to new comment
                    $("html, body").animate({ scrollTop: $('div-comment-'+newCommentID).offset().top }, 1000);
                  }  
                  commentArea = $('form#'+attachmentID+'-comment-form textarea');
                  if(commentArea.length>0){
                    commentArea.val('');
                  }     


                  //remove the status message
                  var commentStatus =  $( '#' + attachmentID + '-comment-form-comment-status');
                   if(commentStatus.length>0){
                    commentStatus.remove();
                   }
          
                } else if (data.state == 'error') {
                    errorMsg = data.html;
                    var commentStatus =  $( '#' +  attachmentID + '-comment-form-comment-status');
                    if(commentStatus.length>0){
                      commentStatus.html(errorMsg);
                    }
                } else if (data.state == 'unauthorised') {
                    errorMsg = data.html;
                    var commentStatus =  $( '#' +  attachmentID + '-comment-form-comment-status');
                    if(commentStatus.length>0){
                      commentStatus.html(errorMsg);
                    }
                } else {
                  //other error
                  errorMsg = data;
                    var commentStatus =  $( '#' +  attachmentID + '-comment-form-comment-status');
                    if(commentStatus.length>0){
                      commentStatus.html(errorMsg);
                    }
                }

                //resize flexslider if functionality exists
                if (typeof htProofingFlexSliderResize == 'function') { 
                  htProofingFlexSliderResize(); 
                } 

            }
    }); 


  //apply to voting action
  votingActions();

  //scroll top
  $("html, body").animate({ scrollTop: 0 }, "slow");
  var hashname = window.location.hash;
  if(hashname!='' && $(hashname).length>0){
    $("html, body").animate({ scrollTop: $(hashname).offset().top }, 1000);
  }
  
  


});

 