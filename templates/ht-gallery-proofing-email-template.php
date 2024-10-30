<?php

if(!class_exists('HT_Gallery_Proofing_Email_Template')){

    class HT_Gallery_Proofing_Email_Template {
        /**
         * Constructor
         */
        public function __construct($recipients, $gallery_id) {
            //array of HT_Gallery_Proofing_Recipients
            $this->recipients = $recipients;
            //the gallery name
            $this->gallery_id = $gallery_id;            
        }

        public function send(){
            //defaults
            $to = '';
            $subject = '';
            $message = '';
            $headers = '';
            $attachments = '';

            $mail_state = true;

            
            $headers = 'From: ' . get_option( 'admin_email' ) . '\r\n';
            $subject = $this->get_gallery_name() . ' ' .  __('is ready for review', 'ht-gallery-proofing');

            //add filter
            add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );


            foreach ($this->recipients as $key => $recipient) {
                $message = sprintf( __('Hi %s, <br/>Just to let you know that gallery %s has been approved, you can view it at %s', 'ht-gallery-proofing'),
                 $recipient->get_name(),
                 $this->get_gallery_name(),
                 $this->get_gallery_permalink()
                );

                $mail_state = $mail_state and wp_mail( $recipient->get_email(), $subject, $message, $headers, $attachments );
            }

            //remove filter
            remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

            
            //return mail_state
            return $mail_state;
            
        }

        /**
        * Return the gallery name
        * @return String gallery name
        */
        public function get_gallery_name(){
            $post = get_post($this->gallery_id);
            if(is_a($post, 'WP_Post')){
                return $post->post_title;
            } else {
                return __('Something went wrong fetching the gallery name', 'ht-gallery-proofing');
            }
        }

        /**
        * Return the gallery permalink
        * @return String gallery permalink
        */
        public function get_gallery_permalink(){
            $post = get_post($this->gallery_id);
            if(is_a($post, 'WP_Post')){
                return get_permalink($post->ID);
            } else {
                return __('Something went wrong fetching the gallery permalink', 'ht-gallery-proofing');
            }
        }


        //Filter - set the email content type
        public function set_html_content_type() {
            return 'text/html';
        }


    } //end class


} //end class test


if(!class_exists('HT_Gallery_Proofing_Recipient')){

    class HT_Gallery_Proofing_Recipient {
        /**
         * Constructor
         */
        public function __construct($name, $email) {
            
            $this->name = $name;
            $this->email = $email;
            
        }

        public function get_name(){
            return $this->name;
        }

        public function get_email(){
            return $this->email;
        }




    } //end class


} //end class test


