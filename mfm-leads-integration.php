<?php
/**
 * Plugin Name: MFM Leads Integration
 * Plugin URI: http://www.monkeyfishmarketing.com  
 * Description: This plugin will allow the implementation of MFM Leads in replacement of popular WP contact forms
 * Version: 1.03
 * Author: Billy Bleasdale
 * License: GPL2
 */


if(!function_exists('mfm_menu')){
    add_action('admin_menu', 'mfm_menu');

    function mfm_menu() {
        add_menu_page('MonkeyFish', 'MonkeyFish', 'manage_options', 'mfm_menu', 'mfmMain',plugins_url( 'mfm-leads-integration/images/mfm-logo.png'));
        add_submenu_page( 'mfm_menu','Welcome','Welcome', 'manage_options',   'mfm_menu',   '__return_null' );
    }
    
    function mfmMain(){
        echo "<h1>Welcome to Monkeyfish Marketing</h1>";
    }
    
}

add_action('admin_menu', 'mfm_leads');

require_once( plugin_dir_path( __FILE__ ) .'auto-updates.php' );
if ( is_admin() ) {
    new MFMLeadsGitHubPluginUpdater( __FILE__, 'bab2k7', "mfm-leads-integration" );
}


function mfm_leads() {
    add_submenu_page('mfm_menu','MFM Leads', 'MFM Leads', 'manage_options', 'mfm_leads', 'mfmLeads');
    
}

function register_mfmleadssettings() { // whitelist options
    /* Global Settings */
    register_setting( 'mfm-leads-global', 'form-codes' );
    register_setting( 'mfm-leads-global', 'selected-forms' );    
}

function mfmLeads(){
    ?>
    <form id="settings-form" method="post" action="options.php">
        <?php 
        settings_fields( 'mfm-leads-global' );
        do_settings_sections( 'mfm-leads-global' );
        ?>
        <div class="tab-data tab-general active">
            <h2>General</h2>
            <div class="input-wrapper">
                <label for="enable-menu">MFM Leads Form Code: </label>
                <br />
                <?php 
                if(function_exists('wpcf7_contact_form_tag_func')){
                    
                ?>
                <label for="enable-menu">Contact Form 7 Forms: </label><br />
                
                <?php
                                               
                        $args = array(
                            'numberposts' => -1,
                            'post_type'   => 'wpcf7_contact_form'
                        );

                        $cf7forms = get_posts( $args );
                        
                        foreach($cf7forms as $cf7form){
                            ?>
                            <div class="input-wrapper">
                                <label for="enable-menu"><?php echo $cf7form->post_name; ?>: </label>
                                <input type="checkbox" class="form-check" id="form-id-cf7<?php echo $cf7form->ID; ?>">
                                Leads Code 
                                <textarea class="leads-code-box" id="leads-code-cf7<?php echo $cf7form->ID; ?>" value=""></textarea>
                            </div>
                            
                            <?php
                            
                        }
                }
                        ?>
                
                
                <input type="hidden" name="form-codes" id="form-codes" value='<?php echo get_option('form-codes'); ?>'>
                <input type="hidden" name="selected-forms" id="selected-forms" value="<?php echo get_option('selected-forms'); ?>">
                
            </div>
        </div>
        <?php submit_button(); ?>
    </form>

<script>
    var $mfmleads = jQuery.noConflict();
    
    
    /* SETUP CHECKBOXES */
    var formList = $mfmleads('#selected-forms').val();
    var formArray = formList.split(',');
    $mfmleads.each(formArray, function( index, value ) {
        $mfmleads('#form-id-'+value).prop('checked', true);
    });
    
    $mfmleads('.form-check').click(function() {        
        var checkedIds = $mfmleads(".form-check:checked").map(function() {
            var formId = this.id.replace('form-id-', '');
            return formId;
        }).get();
        $mfmleads("#selected-forms").val(checkedIds);
    });
    
    
    /* SETUP CODE */
    
    var formsSavedJson = $mfmleads('#form-codes').val();
    var formsSavedObj = $mfmleads.parseJSON('['+formsSavedJson+']');    
    $mfmleads.each(formsSavedObj, function() {        
        $mfmleads("#leads-code-"+this[0].formid).val(this[0].formcode);        
    });    
    $mfmleads('.leads-code-box').focusout(function() {
        var i = 0;
        var formsJson = $mfmleads(".leads-code-box").map(function() {
            var formsObj = [];
            var formId = this.id.replace('leads-code-', '');
            var formCode = this.value;
            formsObj.push({ formid: formId, formcode: formCode });
            return JSON.stringify(formsObj);
            i++;
            return formsObj;
        }).get();
        $mfmleads("#form-codes").val(formsJson);
    });
    
    
</script>



    <?php 
}


function replaceShortcode(){
    if(function_exists('wpcf7_contact_form_tag_func')){
        remove_shortcode('contact-form-7');
        add_shortcode("contact-form-7", function ($atts, $content, $code) {

            $formId = str_replace("cf7-","",$atts['id']);
            $formTitle = $atts['title'];

            $selectedForms = get_option('selected-forms');
            $selectedFormsArray = explode(",",$selectedForms);

            if(in_array($formId,$selectedFormsArray)){
                $formCodes = get_option('form-codes');
                $formCodesArray = json_decode("[".$formCodes."]");

                foreach ($formCodesArray as $singleFormCode ){
                    $singleFormCodeObj = $singleFormCode[0];
                    if($singleFormCodeObj->formid == $formId){
                        $result = $singleFormCodeObj->formcode;
                    }
                }
            }
            else{
                $newAtts = shortcode_atts( array(
                            'id' => $atts['id'],
                            'title' => $atts['title'],
                            'html_id' => '',
                            'html_name' => '',
                            'html_class' => '',
                            'output' => 'form' ), $atts );

                $result = wpcf7_contact_form_tag_func($newAtts, $content, $code);
            }

            return $result;
        });
    }
    
    if(function_exists('ninja_forms_shortcode')){
        /* remove_shortcode('ninja_form');
        add_shortcode("ninja_form", function ($atts, $content, $code) {

            $formId = $atts['id'];
            $formTitle = $atts['title'];

            $selectedForms = get_option('selected-forms');
            $selectedFormsArray = explode(",",$selectedForms);

            if(in_array($formId,$selectedFormsArray)){
                $formCodes = get_option('form-codes');
                $formCodesArray = json_decode("[".$formCodes."]");

                foreach ($formCodesArray as $singleFormCode ){
                    $singleFormCodeObj = $singleFormCode[0];
                    if($singleFormCodeObj->formid == $formId){
                        $result = $singleFormCodeObj->formcode;
                    }
                }
            }
            else{
                $newAtts = shortcode_atts( array(
                            'id' => $atts['id'],
                            'title' => $atts['title'],
                            'html_id' => '',
                            'html_name' => '',
                            'html_class' => '',
                            'output' => 'form' ), $atts );

                $result = wpcf7_contact_form_tag_func($newAtts, $content, $code);
            }

            return $result;
        }); */
    } 
    
}

add_action('init','replaceShortcode');


if ( is_admin() ){ // admin actions
  add_action( 'admin_init', 'register_mfmleadssettings' );
} else {
  // non-admin enqueues, actions, and filters
}
