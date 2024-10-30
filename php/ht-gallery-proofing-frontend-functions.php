<?php
class HT_Gallery_Proofing_Public_Functions {
    

    /**
     * Constructor
     */
    public function __construct() {
        //add actions
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_ht_gallery_proofing_frontend_scripts_and_styles' ) );
        add_action( 'wp_head', array( $this, 'upvote_downvote_header' ) );
        add_action( 'wp_head', array( $this, 'approve_album_header' ) );


        //add filters
        add_filter( 'the_content', array($this, 'proofing_content_filter') , 20 );
        add_filter( 'comment_post_redirect', array( $this, 'ht_gallery_proofing_redirect_after_comment' ), 10, 2 );

        //ajax filters
        add_action( 'wp_ajax_ht_proofing_vote', array( $this, 'ht_proofing_vote_callback' ) );
        add_action( 'wp_ajax_nopriv_ht_proofing_vote', array( $this, 'ht_proofing_vote_callback' ) );

        add_action( 'wp_ajax_ht_proofing_comments', array( $this, 'ht_proofing_comments_callback' ) );
        add_action( 'wp_ajax_nopriv_ht_proofing_comments', array( $this, 'ht_proofing_comments_callback' ) );

    }

    /* VOTING ACTIONS */

    /**
    * Upvote attachment/image action
    * @param (string) $attachment_id
    */
    public function upvote_attachment($attachment_id){
        //stop user upvoting twice
        if( $this->has_user_voted_up($attachment_id) == true )
            return;

        //unvote down
        $this->remove_downvote_attachment($attachment_id);
        $old_count = get_post_meta($attachment_id, 'upvotes_count', true);

        $old_count_int = intval($old_count); //this will be zero if not yet set
        $new_count_int = $old_count_int+1;

        //save new count
        update_post_meta($attachment_id, 'upvotes_count', $new_count_int );

        //save vote
        $votes = get_post_meta($attachment_id, 'upvotes', true);
        $votes[] = new HT_Gallery_Proofing_Upvote();
        update_post_meta($attachment_id, 'upvotes', $votes );
    }

    /**
    * Remove upvote from attachment/image
    * @param (string) $attachment_id
    */
    public function remove_upvote_attachment($attachment_id){
        $old_count = get_post_meta($attachment_id, 'upvotes_count', true);

        $old_count_int = intval($old_count); //this will be zero if not yet set
        if($old_count_int==0)
            return;

        //save vote
        $votes = get_post_meta($attachment_id, 'upvotes', true);
        if($votes=='')
            return;
        //test vote, only used to remove vote
        $test_vote = new HT_Gallery_Proofing_Upvote();

        foreach ($votes as $key => $vote) {
            if($vote->user_id==0 && $test_vote->user_id==0){
                if($test_vote->ip == $vote->ip){
                    //unset 
                    unset($votes[$key]);
                    $this->decrease_upvote_count($attachment_id);
                    break;
                }
            } else {
                if($test_vote->user_id == $vote->user_id){
                    //unset 
                    unset($votes[$key]);
                    $this->decrease_upvote_count($attachment_id);
                    break;
                }
                    
            }
        }
        update_post_meta($attachment_id, 'upvotes', $votes );
    }

    /**
    * Decrease the upvote count
    * @param (string) $attachment_id
    */
    public function decrease_upvote_count($attachment_id){
        $old_count = get_post_meta($attachment_id, 'upvotes_count', true);

        $old_count_int = intval($old_count); //this will be zero if not yet set
        if($old_count_int==0)
            return;
        $new_count_int = $old_count_int-1;
        //save new count
        update_post_meta($attachment_id, 'upvotes_count', $new_count_int );

    }


    /**
    * Downvote attachment/image action
    * @param (string) $attachment_id
    */
    public function downvote_attachment($attachment_id){
        //stop user downvoting twice
        if( $this->has_user_voted_down($attachment_id) == true )
            return;

        //unvote up
        $this->remove_upvote_attachment($attachment_id);
        $old_count = get_post_meta($attachment_id, 'downvotes_count', true);
        $old_count_int = intval($old_count); //this will be zero if not yet set
        $new_count_int = $old_count_int+1;
        //save new count
        update_post_meta($attachment_id, 'downvotes_count', $new_count_int );

        //save vote
        $votes = get_post_meta($attachment_id, 'downvotes', true);
        $votes[] = new HT_Gallery_Proofing_Downvote();
        update_post_meta($attachment_id, 'downvotes', $votes );
        
    }

    /**
    * Remove downvote from attachment/image
    * @param (string) $attachment_id
    */
    public function remove_downvote_attachment($attachment_id){
        $old_count = get_post_meta($attachment_id, 'downvotes_count', true);

        $old_count_int = intval($old_count); //this will be zero if not yet set
        if($old_count_int==0)
            return;


        //save vote
        $votes = get_post_meta($attachment_id, 'downvotes', true);
        if($votes=='')
            return;
        //test vote, only used to remove vote
        $test_vote = new HT_Gallery_Proofing_Downvote();

        foreach ($votes as $key => $vote) {
            if($vote->user_id==0 && $test_vote->user_id==0){
                if($test_vote->ip == $vote->ip){
                    //unset 
                    unset($votes[$key]);
                    $this->decrease_downvote_count($attachment_id);
                    break;
                }
            } else {
                if($test_vote->user_id == $vote->user_id){
                    //unset 
                    unset($votes[$key]);
                    $this->decrease_downvote_count($attachment_id);
                    break;
                }
                    
            }
        }
        update_post_meta($attachment_id, 'downvotes', $votes );
    }

    /**
    * Decrease downvote count for attachment/image
    * @param (string) $attachment_id
    */
    public function decrease_downvote_count($attachment_id){
        $old_count = get_post_meta($attachment_id, 'downvotes_count', true);

        $old_count_int = intval($old_count); //this will be zero if not yet set
        if($old_count_int==0)
            return;
        $new_count_int = $old_count_int-1;
        //save new count
        update_post_meta($attachment_id, 'downvotes_count', $new_count_int );
    }

    /**
    * Get the upvotes count attachment/image
    * @param (string) $attachment_id
    * @return (int/string) the vote count 
    */
    public function get_attachment_upvotes_count($attachment_id){ 
        $upvotes_count = get_post_meta($attachment_id, 'upvotes_count', true); 
        if($upvotes_count=='')
            $upvotes_count=0;
        return $upvotes_count;
    }

    /**
    * Get the downvotes count attachment/image
    * @param (string) $attachment_id
    * @return (int/string) the vote count 
    */
    public function get_attachment_downvotes_count($attachment_id){
        $downvotes_count = get_post_meta($attachment_id, 'downvotes_count', true);
        if($downvotes_count=='')
            $downvotes_count = 0;
        return $downvotes_count;
    }

    /**
    * Test whether post is a proofing gallery
    * @param (string) $post_id
    * @return  (boolean) true if proofing gallery
    */
    public function is_proofing_gallery($post_id){
        if(!is_single($post_id))
            return false;

        //return the option
        return $this->get_gallery_option( $post_id, 'proofing_gallery_option');

    }

    /**
    * Test whether user has voted on attachment/image
    * @param (string) $attachment_id
    * @return (boolean) True if they have voted
    */
    public function has_user_voted($attachment_id){
        //user has voted if there user id and ip are already in the list of votes
        return $this->has_user_voted_up($attachment_id) || $this->has_user_voted_down($attachment_id);
    }

    /**
    * Test whether user has voted up on attachment/image
    * @param (string) $attachment_id
    * @return (boolean) True if they have voted up
    */
    public function has_user_voted_up($attachment_id){
        $votes = get_post_meta($attachment_id, 'upvotes', true);

        if($votes == '')
            return false;

        //comparison object, not saved
        $test_vote = new HT_Gallery_Proofing_Upvote();
        foreach ($votes as $vote) {
            //return true if not anonymous and voted
            if(intval($vote->user_id) > 0 && $vote->user_id == $test_vote->user_id)
                return true;
            //return true if anon and ip the same
            if(intval($test_vote->user_id)==0 && intval($vote->user_id)==0 && $vote->ip==$test_vote->ip )
                return true;
        }
        return false;
    }

    /**
    * Test whether user has voted down on attachment/image
    * @param (string) $attachment_id
    * @return (boolean) True if they have voted down
    */
    public function has_user_voted_down($attachment_id){
        $votes = get_post_meta($attachment_id, 'downvotes', true);

        if($votes == '')
            return false;

        //comparison object, not saved
        $test_vote = new HT_Gallery_Proofing_Downvote();
        foreach ($votes as $vote) {
            //return true if not anonymous and voted
            if(intval($vote->user_id) > 0 && $vote->user_id == $test_vote->user_id)
                return true;
            //return true if anon and ip the same
            if(intval($test_vote->user_id)==0 && intval($vote->user_id)==0 && $vote->ip==$test_vote->ip )
                return true;
        }
        return false;
    }

    /* PERMISSIONS FUNCTIONS */

    public function can_user_view_upvotes( $user_id, $gallery_id, $attachment_id ){
        //proofing_allow_approvals_option 
        return $this->get_gallery_option( $gallery_id, 'proofing_allow_approvals_option' );
    }

    public function can_user_make_upvotes( $user_id, $gallery_id, $attachment_id ){
        //proofing_allow_approvals_option
        $state = get_post_meta($gallery_id, 'proofing_state', true);
        if($state =="APPROVED" && $this->is_approval_action( $gallery_id, 'lock_approvals'))
            return false;
        else
            return $this->get_gallery_option( $gallery_id, 'proofing_allow_approvals_option' );
    }

    public function can_user_view_downvotes( $user_id, $gallery_id, $attachment_id ){
        //proofing_allow_disapprovals_option
        return $this->get_gallery_option( $gallery_id, 'proofing_allow_disapprovals_option' );
    }

    public function can_user_make_downvotes( $user_id, $gallery_id, $attachment_id ){
        //proofing_allow_disapprovals_option
        $state = get_post_meta($gallery_id, 'proofing_state', true);
        if($state =="APPROVED" && $this->is_approval_action( $gallery_id, 'lock_approvals'))
            return false;
        else
            return $this->get_gallery_option( $gallery_id, 'proofing_allow_disapprovals_option' );
    }

    public function can_user_view_attachment_comments( $user_id, $gallery_id, $attachment_id ){
        //proofing_image_comments_option 
        return $this->get_gallery_option( $gallery_id, 'proofing_image_comments_option' );
    }

    public function can_user_make_attachment_comments( $user_id, $gallery_id, $attachment_id ){
        //proofing_image_comments_option
        $state = get_post_meta($gallery_id, 'proofing_state', true);
        if($state =="APPROVED" && $this->is_approval_action( $gallery_id, 'lock_image_comments'))
            return false;
        else
            return $this->get_gallery_option( $gallery_id, 'proofing_image_comments_option' );
    }

    public function can_user_view_album_comments( $user_id, $gallery_id ){
        //proofing_album_comments_option
        return $this->get_gallery_option( $gallery_id, 'proofing_album_comments_option' );
    }

    public function can_user_make_album_comments( $user_id, $gallery_id ){
        //proofing_album_comments_option 
        $state = get_post_meta($gallery_id, 'proofing_state', true);
        if($state =="APPROVED" && $this->is_approval_action( $gallery_id, 'lock_gallery_comments'))
            return false;
        else
            return $this->get_gallery_option( $gallery_id, 'proofing_album_comments_option' );
    }

    public function can_user_approve_album( $user_id, $gallery_id ){
        //proofing_album_approval_option
        return $this->get_gallery_option( $gallery_id, 'proofing_album_approval_option' );
    }

    /**
    * Get the option meta true/false for specified gallery and option name
    * @param (string) $gallery_id The gallery/post_id of the gallery
    * @param (string) $option_name The option to fetch
    * @return (boolean) True if option is set
    */
    public function get_gallery_option( $gallery_id, $option_name ){
        $option_status = get_post_meta($gallery_id, $option_name, true);
        if(empty($option_status)){
            return false;
        } else {
            return true;
        }
    }


    /* OUTPUT FUNCTIONS */

    public function echo_upvotes_for_attachment( $attachment_id ){
        $this->echo_votes_for_attachment( 'up', $attachment_id);
    }

    public function echo_downvotes_for_attachment( $attachment_id ){
        $this->echo_votes_for_attachment( 'down', $attachment_id);
    }

    /**
    * Display the votes for the attachment/image
    * @param (string) $direction Either 'up' or 'down'
    * @param (string) $attachment_id Gallery to display for
    */
    public function echo_votes_for_attachment( $direction, $attachment_id){
        ?>
            <div class="ht-gallery-proofing-votes ht-gallery-proofing-votes-<?php echo $direction; ?>" id="ht-gallery-proofing-votes-<?php echo $attachment_id; ?>">
        <?php
        //get the votes
        $votes = get_post_meta( $attachment_id, $direction.'votes', true );
        if(is_array($votes) && count($votes)>0){
            $anonymous_count = 0;
            ?>
                <ul class="ht-gallery-proofing-vote-list">
            <?php
                        foreach ($votes as $key => $vote) {
                           ?>
                                
                           <?php
                                if($vote->user_id==0){
                                    $anonymous_count = $anonymous_count+1;
                                } else {
                                    $user = get_userdata($vote->user_id);
                                    if(is_a($user, 'WP_User')){
                                        ?>
                                            <li class="ht-gallery-proofing-vote-list-item">
                                        <?php                                        
                                        $display_name = $user->display_name;
                                        echo $display_name;
                                        ?>
                                            </li> <!--ht-gallery-proofing-vote-list-item-->
                                        <?php
                                    } else {
                                        _e('Cannot get user info', 'ht-gallery-proofing');
                                    }
                                }
                           ?>
                                
                           <?php
                        }
                        if($anonymous_count>0){
                            ?>
                                <li class="ht-gallery-proofing-vote-list-item">
                            <?php
                                echo sprintf(__('%d anonymous users', 'ht-gallery-proofing'), $anonymous_count);
                            ?>
                                </li> <!--ht-gallery-proofing-vote-list-item-->
                            <?php
                        }

            ?>
                </ul> <!--ht-gallery-proofing-vote-list -->
            <?php

        } else {
            ?>
                <div class="ht-gallery-proofing-novotes"><?php echo _e('No Votes', 'ht-gallery-proofing'); ?></div>
            <?php
        }
        ?>
            </div> <!-- ht-gallery-proofing-votes-direction -->
        <?php
    }


    /**
    * Display the proofing content
    * @param (string) $content The original content, this is unused
    * @return (null) This does not append to the filter, only override it
    */
    public function proofing_content_filter( $content ){
        global $post;


        //return unfiltered if not ht_gallery_post or proofing gallery
        if( !isset($post) || $post->post_type!='ht_gallery_post' || $this->is_proofing_gallery($post->ID)==false )
            return $content;

        $user_id = $this->ht_gallery_proofing_get_current_user();

        // Get gallery images 
        $ht_homepage_gallery = get_post_meta( $post->ID, '_ht_gallery_images', true );
        $ht_homepage_gallery = is_a($ht_homepage_gallery, 'WP_Error') ? array() : explode(",", $ht_homepage_gallery);
        //halt on error
        if($ht_homepage_gallery==is_a($ht_homepage_gallery, 'WP_Error')){
            return $content;
        }
        $ht_homepage_gallery_image = count( $ht_homepage_gallery ) > 0 ? $ht_homepage_gallery[0] : null;
        ?>


        <?php if ($ht_homepage_gallery ) {  ?>
            <div id="single-gallery-proofing" data-ht-gallery-post="<?php echo $post->ID; ?>">
            
            <div id="single-gallery-proofing-slider">
                <ul class="slides">
                <?php $gallery_image_number = 1; ?>
                <?php $gallery_image_total = count( $ht_homepage_gallery ); ?>
                    <?php foreach($ht_homepage_gallery as $gallery_image) { ?>
                        <?php 
                            // Get gallery image meta
                            $gallery_image_title = get_post_field('post_title', $gallery_image);
                            $gallery_image_caption = get_post_field('post_excerpt', $gallery_image);
                            $gallery_image_full_src = wp_get_attachment_image_src( $gallery_image, 'full' );
                            
                        ?>
                        <li>
                            <a id="proofing-<?php echo $gallery_image; ?>" name="proofing-<?php echo $gallery_image; ?>"></a>
                            <div id="single-gallery-proofing-nav">
                            <?php _e('Image ', 'ht-gallery-proofing'); echo $gallery_image_number;  _e(' of ', 'ht-gallery-proofing'); echo $gallery_image_total; ?>
                            </div>
                            <div class="gallery-proofing-img-wrap">
                                <?php 
                                echo wp_get_attachment_image( $gallery_image, 'gallery-single' ); 
                                //echo the voting
                                $this->gallery_image_votes($gallery_image, $post->ID); 
                                ?>
                            </div>
                            <div class="clearfix">
								<?php //echo the title and caption
                                if ($gallery_image_title != '' || $gallery_image_caption != '') { ?>
                                <div class="gallery-proofing-img-meta">
                                <?php if ($gallery_image_title != '') { ?>
                                    <h2><?php echo $gallery_image_title; ?></h2>
                                <?php }
                                if ($gallery_image_caption != '') { ?>
                                    <p><?php echo $gallery_image_caption; ?></p>
                                <?php } ?>
                                
                                </div>
                                <?php } ?>
                                <?php //echo the comments
                                $this->ht_gallery_image_comments($gallery_image, $post->ID);
                                ?>
                            </div>
                        </li>       
                    <?php $gallery_image_number++; ?>
                    <?php } // end foreach ?>
                </ul>
                </div>
                <?php if(($this->can_user_approve_album($user_id, $post->ID)) ||($this->can_user_view_album_comments($user_id, $post->ID)==false)){ ?>
                    <div class="proofing-submit">
                    <?php if(!$this->can_user_view_album_comments($user_id, $post->ID)==false){ ?>
                    	<button id="view-album-comments"><?php _e('Album Comments', 'ht-gallery-proofing'); ?></button>
                     <?php } ?>   
                        
                        <?php if($this->can_user_approve_album($user_id, $post->ID)){ ?>
                        <form action="?" method="post" id="approval-form-<?php echo $post->ID; ?>" class="submit-as-approved">
                                <?php wp_nonce_field( 'ht_gallery_proofing_approve_action', 'ht_gallery_proofing_approve_security' ); ?>
                                <input type="hidden" id="ht_proofing_approve" name="ht_proofing_approve" value="ht_proofing_approve">
                                
                                <input name="submit" type="submit" id="submit" value="<?php _e('Approve Album', 'ht-gallery-proofing'); ?>">
                        </form>
                        <?php } ?>
                    </div><!-- proofing-submit -->
                 <?php } ?>
                 <div class="album-comments">
                    <h2 class="album-comments-title deco"><span><?php _e('Album Comments', 'ht-gallery-proofing'); ?></span></h2>
                    <div class="proofing-album-comments" name="proofing-album-comments-<?php echo $post->ID; ?>" id="proofing-album-comments-<?php echo $post->ID; ?>">
                        <?php

                            if($this->can_user_view_album_comments($user_id, $post->ID)==false){
                                _e('You cannot view comments for this gallery', 'ht-gallery-proofing');
                            } else {
                                $comments = get_comments( 'status=approve&post_id=' . $post->ID );
                        ?>
                                <ul id="comment-area-<?php echo $post->ID; ?>" class="comment-area">
                        <?php
                                if(function_exists('ht_comment')){
                                    wp_list_comments( array(
                                        'max_depth'=>1,
                                        'reverse_top_level' => true,
                                        'callback'=>'ht_comment'),
                                    $comments );
                                } else {
                                    wp_list_comments( array(
                                        'max_depth'=>1,
                                        'reverse_top_level' => true),
                                    $comments );
                                }
                                
                        ?>
                                </ul>
                        <?php } //end can user view album comments ?>
                        <a id="proofing-album-commentarea-<?php echo $post->ID; ?>" name="proofing-album-commentarea-<?php echo $post->ID; ?>"></a>
                        <?php
                            if($this->can_user_make_album_comments($user_id, $post->ID))
                                comment_form( array('id_form'=>$post->ID."-comment-form", 'logged_in_as'=>''), $post->ID );                                         
                        ?>
                    </div><!-- proofing-album-comments -->
                </div><!-- album-comments -->
            </div> 

        <?php
        } // end if $ht_homepage_gallery
    }

    /**
    * Display the votes on the image
    * @param (string) $gallery_image The id of the image/attachment
    * @param (string) $post_id The post id of the current gallery
    */
    public function gallery_image_votes($gallery_image, $post_id){
            $user_id = $this->ht_gallery_proofing_get_current_user();

            $upvotes_count = $this->get_attachment_upvotes_count($gallery_image);
            $downvotes_count = $this->get_attachment_downvotes_count($gallery_image);
            $voted_up = $this->has_user_voted_up($gallery_image) ? 'votedup' : '';
            $voted_down = $this->has_user_voted_down($gallery_image) ? 'voteddown' : '';

            $voteup_class =  $this->can_user_make_upvotes( $user_id, $post_id, $gallery_image ) ? 'canvote' : 'cantvote';
            $votedown_class = $this->can_user_make_downvotes( $user_id, $post_id, $gallery_image ) ? 'canvote' : 'cantvote';
            $un_voteup_class = $this->can_user_make_upvotes( $user_id, $post_id, $gallery_image ) ? 'canvote' : 'cantvote';
            $un_votedown_class = $this->can_user_make_downvotes( $user_id, $post_id, $gallery_image ) ? 'canvote' : 'cantvote';
       

            $voteup_url =  $this->can_user_make_upvotes( $user_id, $post_id, $gallery_image ) ? wp_nonce_url('?vote=up&attachment=' .  $gallery_image . '#proofing-' . $gallery_image , 'voteup') : '#';
            $votedown_url = $this->can_user_make_downvotes( $user_id, $post_id, $gallery_image ) ?  wp_nonce_url('?vote=down&attachment=' .  $gallery_image . '#proofing-' . $gallery_image , 'votedown') : '#';
            $un_voteup_url = $this->can_user_make_upvotes( $user_id, $post_id, $gallery_image ) ? wp_nonce_url('?vote=unup&attachment=' .  $gallery_image . '#proofing-' . $gallery_image, 'un_voteup') : '#';
            $un_votedown_url = $this->can_user_make_downvotes( $user_id, $post_id, $gallery_image ) ? wp_nonce_url('?vote=undown&attachment=' .  $gallery_image . '#proofing-' . $gallery_image, 'un_votedown') : '#';
        ?>
        <div class="gallery-proofing-voting-box <?php echo $voted_up; ?> <?php echo $voted_down; ?>" id="gallery-proofing-voting-box-<?php echo $gallery_image; ?>" >
            <?php if(empty($voted_up)): ?>
                <a href="<?php echo $voteup_url; ?>"  data-attachment-id="<?php echo $gallery_image; ?>" data-gallery-id="<?php echo $post_id; ?>" onclick="return false;" id="ht-up-<?php echo $gallery_image; ?>" data-action="up" title="<?php _e('Vote this up', 'ht-gallery-proofing'); ?>" class="gallery-proofing-vote-action gallery-proofing-upvote <?php echo $voteup_class; ?>">
                    <i class="fa fa-thumbs-o-up"></i><span><?php echo $upvotes_count; ?></span>
                    <?php $this->echo_upvotes_for_attachment($gallery_image); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo $un_voteup_url; ?>"  data-attachment-id="<?php echo $gallery_image; ?>" data-gallery-id="<?php echo $post_id; ?>" onclick="return false;"  id="ht-up-<?php echo $gallery_image; ?>" data-action="unup" title="<?php _e('Remove upvote', 'ht-gallery-proofing'); ?>" class="gallery-proofing-vote-action gallery-proofing-upvote voted  <?php echo $un_voteup_class; ?>">
                    <i class="fa fa-thumbs-o-up"></i><span><?php echo $upvotes_count; ?></span>
                    <?php
                $this->echo_upvotes_for_attachment($gallery_image);
            ?>
                </a>
            <?php endif; ?>
            
            <?php if(empty($voted_down)): ?>
                <a href="<?php echo $votedown_url; ?>" data-attachment-id="<?php echo $gallery_image; ?>" data-gallery-id="<?php echo $post_id; ?>" onclick="return false;"  id="ht-down-<?php echo $gallery_image; ?>" data-action="down" title="<?php _e('Vote this down', 'ht-gallery-proofing'); ?>" class="gallery-proofing-vote-action gallery-proofing-downvote <?php echo $votedown_class; ?>">
                    <i class="fa fa-thumbs-o-down"></i><span><?php echo $downvotes_count; ?></span>
                    <?php $this->echo_downvotes_for_attachment($gallery_image); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo $un_votedown_url; ?>" data-attachment-id="<?php echo $gallery_image; ?>" data-gallery-id="<?php echo $post_id; ?>" onclick="return false;"  id="ht-down-<?php echo $gallery_image; ?>" data-action="undown" title="<?php _e('Remove downvote', 'ht-gallery-proofing'); ?>" class="gallery-proofing-vote-action gallery-proofing-downvote voted   <?php echo $un_votedown_class; ?>">
                    <i class="fa fa-thumbs-o-down"></i><span><?php echo $downvotes_count; ?></span>
                    <?php $this->echo_downvotes_for_attachment($gallery_image); ?>
                </a>
            <?php endif; ?>
            
        </div><!-- gallery-proofing-voting-box -->
        <?php
    }

    /**
    * Display the comments for the image
    * @param (string) $gallery_image The id of the image/attachment
    * @param (string) $post_id The post id of the current gallery
    */
    public function ht_gallery_image_comments($gallery_image, $post_id){
        $user_id = $this->ht_gallery_proofing_get_current_user();
        ?>
        <div class="proofing-comments" name="proofing-comments-<?php echo $gallery_image; ?>" id="proofing-comments-<?php echo $gallery_image; ?>">
            <h3 id="proofing-comments-title" class="deco"><span><?php _e('Image Comments', 'ht-gallery-proofing'); ?></span></h3>
                <a id="proofing-commentarea-<?php echo $gallery_image; ?>" name="proofing-commentarea-<?php echo $gallery_image; ?>"></a>
            <?php
                $this->ht_gallery_image_comment_list($gallery_image);
                if($this->can_user_make_attachment_comments($user_id, $post_id, $gallery_image)){
                    comment_form( array('id_form'=>$gallery_image."-comment-form", 'logged_in_as'=>''), $gallery_image);  
                }
                                                              
            ?>
        </div><!-- proofing-comments -->
        <?php
    }

    /**
    * Display the comments list for the image
    * @param (string) $gallery_image The id of the image/attachment
    * @param (string) $post_id The post id of the current gallery
    */
    public function ht_gallery_image_comment_list($gallery_image){
            
            $comments = get_comments( 'status=approve&post_id=' . $gallery_image );
            ?>
            <ul class="comment-area" id="comment-area-<?php echo $gallery_image; ?>">
            <?php
                if(function_exists('ht_comment')){
                    wp_list_comments( array(
                        'max_depth'=>1,
                        'reverse_top_level' => true,
                        'callback'=>'ht_comment'),
                    $comments );
                } else {
                    wp_list_comments( array(
                        'max_depth'=>1,
                        'reverse_top_level' => true),
                    $comments );
                }
            ?>
            </ul>
            <?php

    }


    /**
    * Filter - Redirect after comment posting, no longer strictly required as comments have been ajaxified
    * @param (string) $location The location to redirect
    * @param (string) $comment The comment object
    */
    public function ht_gallery_proofing_redirect_after_comment($location, $comment){

        global $post;

        $ht_proofing_return = $_POST['ht_proofing_return_post_id'];
        $gallery_permalink = get_permalink( $ht_proofing_return );

        
        //if we want to return to the gallery set this as the location
        if(isset($ht_proofing_return) && isset($gallery_permalink)){
             $permalink_append = '';
            if(isset($comment)&&isset($comment->comment_ID)){
                $permalink_append = '#div-comment-'.$comment->comment_ID;
            } else {
                //if no comment just scroll to comment id
                $permalink_append = '#proofing-commentarea-' . $post->ID;
            }
            return $gallery_permalink . $permalink_append;
        } else {
            return $location;
        }
        
    }

    /**
    * WP_Head Filter - voteup/down, no longer strictly required as voting has been ajaxified
    */
    public function upvote_downvote_header(){
        global $post;
        if(!isset($post) || $this->is_proofing_gallery($post->ID)==false)
            return;

        //check proofing gallery
        if($post->post_type!='ht_gallery_post')
            return;


        $vote = array_key_exists('vote', $_GET) ? $_GET['vote'] : null;

        $attachment_id = array_key_exists('attachment', $_GET) ? $_GET['attachment'] : null;

        $nonce = array_key_exists('_wpnonce', $_GET) ? $_GET['_wpnonce'] : null;

        if(!empty($attachment_id) && $vote=='up'){
            if ( ! wp_verify_nonce( $nonce, 'voteup' ) ) {
                 die( 'Security check' ); 
            } else {
                 $this->upvote_attachment($attachment_id);
            }            
        } elseif (!empty($attachment_id) && $vote=='down') {
            if ( ! wp_verify_nonce( $nonce, 'votedown' ) ) {
                 die( 'Security check' ); 
            } else {
                 $this->downvote_attachment($attachment_id);
            } 
        } elseif (!empty($attachment_id) && $vote=='unup') {
            if ( ! wp_verify_nonce( $nonce, 'un_voteup' ) ) {
                 die( 'Security check' ); 
            } else {
                 $this->remove_upvote_attachment($attachment_id);
            } 
        } elseif (!empty($attachment_id) && $vote=='undown') {
            if ( ! wp_verify_nonce( $nonce, 'un_votedown' ) ) {
                 die( 'Security check' ); 
            } else {
                 $this->remove_downvote_attachment($attachment_id);
            }   
        }

    }

    /**
    * WP_Head Filter - voteup/down, no longer strictly required as voting has been ajaxified
    */
    public function approve_album_header(){
        global $post;


        //security check
        // Check if our nonce is set.
        if ( ! isset( $_POST['ht_gallery_proofing_approve_security'] ) )
            return;

        $nonce = $_POST['ht_gallery_proofing_approve_security'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'ht_gallery_proofing_approve_action' ) )
            return;


        if(!isset($post) || $this->is_proofing_gallery($post->ID)==false)
            return;


        //check proofing gallery
        if($post->post_type!='ht_gallery_post')
            return;

        $approve_album = array_key_exists('ht_proofing_approve', $_POST) ? $_POST['ht_proofing_approve'] : null;

        if(!empty($approve_album)){
            //set the album approval state
            update_post_meta($post->ID, 'proofing_state', 'APPROVED');
        }

    }




    /**
    * Enqueue scripts and styles
    */
    public function enqueue_ht_gallery_proofing_frontend_scripts_and_styles(){
        global $post;
        if( isset($post) && $post->post_type == 'ht_gallery_post' ) {
            if( !current_theme_supports( 'hero-gallery-proofing-frontend-styles' ) ){
                wp_enqueue_style( 'ht-gallery-proofing-frontend-style', plugins_url( 'css/ht-gallery-proofing-frontend-style.css', dirname(__FILE__) ));
            }
            
            wp_enqueue_script( 'ht-gallery-proofing-frontend-scripts', plugins_url( 'js/ht-gallery-proofing-frontend-scripts.js', dirname(__FILE__) ), array( 'jquery', 'jquery-form' ), 1.0, true );
            wp_enqueue_script('comment-reply');
            wp_localize_script( 'ht-gallery-proofing-frontend-scripts', 'framework', array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ), 
                'cantvotetext' => __('You cannot vote on this', 'ht-gallery-proofing'),
                'commentsurl' => get_site_url(get_current_blog_id(), '/wp-comments-post.php'), 
                'postingcomment' => __('Posting Comment...', 'ht-gallery-proofing'),
                'ajaxnonce' => wp_create_nonce('ht-proofing-ajax-nonce') ) );
        } 
    }

    /**
    * Ajax Voting
    */
    public function ht_proofing_vote_callback(){
        
        //check nonce here (and check it's an ajax request?)
        $nonce = isset($_POST['ajax_nonce']) ? $_POST['ajax_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ht-proofing-ajax-nonce' ) ) 
            die( 'Security check' );

        $user_id = $this->ht_gallery_proofing_get_current_user();

        //ajax
        //get the attachment
        $attachment_id = array_key_exists( 'attachment_id', $_POST ) ? $_POST['attachment_id'] : '';
        //get the gallery
        $gallery_id = array_key_exists( 'gallery_id', $_POST ) ? $_POST['gallery_id'] : '';
        //get the vote direction
        $vote_direction = array_key_exists( 'vote_direction', $_POST ) ? $_POST['vote_direction'] : '';
        if(!empty($attachment_id) && !empty($vote_direction)){
            $state = get_post_meta($gallery_id, 'proofing_state', true);
            if($state =="APPROVED" && $this->is_approval_action( $gallery_id, 'lock_approvals')){
                die('Locked');
            } elseif ($vote_direction=='up'){
                if($this->can_user_make_upvotes($user_id, $gallery_id, $attachment_id)==false)
                    die('Unauthorised');
                else
                    $this->upvote_attachment($attachment_id);
            } elseif ($vote_direction=='down'){
                if($this->can_user_make_downvotes($user_id, $gallery_id, $attachment_id)==false)
                    die('Unauthorised');
                else
                    $this->downvote_attachment($attachment_id);
            } elseif ($vote_direction=='unup'){
                if($this->can_user_make_upvotes($user_id, $gallery_id, $attachment_id)==false)
                    die('Unauthorised');
                else
                    $this->remove_upvote_attachment($attachment_id);
            } elseif ($vote_direction=='undown'){
                if($this->can_user_make_downvotes($user_id, $gallery_id, $attachment_id)==false)
                    die('Unauthorised');
                else
                    $this->remove_downvote_attachment($attachment_id);
            }
            //echo the new gallery image votes
            $this->gallery_image_votes($attachment_id, $gallery_id);
        }
        die(); // this is required to return a proper result
    }

    /**
    * Check if an approval action is set for a given gallery
    * @param String $gallery_id The gallery to check
    * @param String $action The action to check
    * @return Boolean true if action is set for gallery, else false
    */
    function is_approval_action( $gallery_id, $action ){
        $approval_actions_string = get_post_meta( $gallery_id, 'proofing_approval_actions', true );
        $approval_actions_array = explode(',', $approval_actions_string);
        if(in_array($action, $approval_actions_array)){
            return true;
        } else {
            return false;
        }
    }

    /**
    * Ajax Comments
    */
    public function ht_proofing_comments_callback(){
        //check nonce here (and check it's an ajax request?)
        $nonce = isset($_POST['ajax_nonce']) ? $_POST['ajax_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ht-proofing-ajax-nonce' ) ) 
            die( 'Security check' );

        
        $comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
        $gallery_ID = isset($_POST['ht_proofing_return_post_id']) ? (int) $_POST['ht_proofing_return_post_id'] : 0;
        $comment_content = $_POST['comment'] ? wp_kses_post($_POST['comment']): '';
        $user = wp_get_current_user();
        if(is_user_logged_in() && is_a($user, 'WP_User')){

            $user_id  = $this->ht_gallery_proofing_get_current_user();
            if ( empty( $user->display_name ) )
                $user->display_name = $user->user_login;
            $comment_author       = wp_slash( $user->display_name );
            $comment_author_email = wp_slash( $user->user_email );
            $comment_author_url   = wp_slash( $user->user_url );
        } else {
            $user_id  = 0;
            $comment_author       = ( isset($_POST['author']) )  ? trim(strip_tags($_POST['author'])) : null;
            $comment_author_email = ( isset($_POST['email']) )   ? trim($_POST['email']) : null;
            $comment_author_url   = ( isset($_POST['url']) )     ? trim($_POST['url']) : null;
        }

        //check the user is authorised to make comments (album or attachemnt)
        if( empty($gallery_ID) && ( $this->can_user_make_album_comments( $user_id, $comment_post_ID ) != true ) ) {
            //gallery comment
            $json_response['state'] = 'unauthorised';
            $json_response['html'] = __('You are not authorised to comment on this gallery', 'ht-gallery-proofing');
            $json_response['attachmentID'] = $comment_post_ID;
            //echo the response and die
            echo json_encode($json_response);
            die();
        } elseif( !empty($gallery_id) && $this->can_user_make_attachment_comments( $user_id, $gallery_ID, $comment_post_ID ) != true ) {
            //todo - improve security of this check
            //attachement comment
            $json_response['state'] = 'unauthorised';
            $json_response['html'] = __('You are not authorised to comment on this image', 'ht-gallery-proofing');
            $json_response['attachmentID'] = $comment_post_ID;
            //echo the response and die
            echo json_encode($json_response);
            die();
        }

       
        $data = compact('comment_post_ID', 'comment_content', 'user_id', 'comment_author', 'comment_author_email', 'comment_author_url');
        
        //$comment_id = wp_insert_comment($data);
        $comment_id = wp_new_comment($data);
        //@todo things that could be done here - duplicate testing, empty testing, comment notification        
        if(!empty($comment_post_ID) && $comment_post_ID>0 && is_int($comment_id) && $comment_id>0){
            
            $json_response['state'] = 'success';
            ob_start( );
            $this->ht_gallery_image_comment_list($comment_post_ID);
            $output = ob_get_clean();
            @ob_end_clean();
            $json_response['html'] = $output;
            $json_response['attachmentID'] = $comment_post_ID;
            $json_response['newCommentID'] = $comment_id;

        } else {
            $json_response['state'] = 'error';
            $json_response['html'] = __('Something went wrong, refresh this page and try again', 'ht-gallery-proofing');
            $json_response['attachmentID'] = $comment_post_ID;
        }

        //echo the response
        echo json_encode($json_response);

        die(); // this is required to return a proper result
    }

    /**
    * Test for AJAX calls, query if requried as all ajax actions hooked onto wp_ajax_{security}_{action-slug} hook
    * @return (boolean) True if ajax call
    */
    public function is_ajax_call(){
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
    * Get the Current user ID
    * @return (int) Current user ID
    */
    public function ht_gallery_proofing_get_current_user(){
        global $current_user;

        get_currentuserinfo();
        $user_id = is_a($current_user, 'WP_User') ? $current_user->ID : 0;

        return $user_id;
    }


} //end class

//run
global $ht_gallery_proofing_public_functions;
$ht_gallery_proofing_public_functions = new HT_Gallery_Proofing_Public_Functions();

if(!function_exists('is_proofing_gallery')){
    function is_proofing_gallery($post_id){
        global $ht_gallery_proofing_public_functions;
        return $ht_gallery_proofing_public_functions->is_proofing_gallery($post_id);
    }
}

