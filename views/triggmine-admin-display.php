<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); ?>

<div class="wrap">
    <h2>Triggmine</h2>
    
    <?php settings_errors(); ?>
    
    <?php if ( !class_exists( 'WooCommerce' ) ) { ?>
        <div class="notice notice-error">
            <h3>WooCommerce not found!</h3> 
        	<p>
        	    Warning! Triggmine requires WooCommerce to work. Please install and activate WooCommerce to use Triggmine.
        	</p>
        </div>
    <?php } else { ?>
    
    <?php 
        $fullBaseURL = get_site_url();
        $baseURL = str_replace('/', '', substr($fullBaseURL, strpos($fullBaseURL, '://') + 3));
    ?>
    
    <div class="notice notice-info">
        <h3>Triggmine v<?php echo TRIGGMINE_VERSION; ?></h3> 
    	<p>
    	    <a href="https://client.triggmine.com/signup?utm_source=<?php echo $baseURL; ?>" target="_blank">Sign Up</a>  
            and visit the 
            <a href="https://triggmine.freshdesk.com/solution/folders/22000161679" target="_blank">Wordpress getting started guide</a> 
            for instructions on configuring TriggMine.
    	</p>
    </div>
    
    <?php $settings = get_option('triggmine_settings'); ?>
    
    <form method="post" action="options.php">
        <div class="card">
        <?php settings_fields( 'triggmine-settings-group' ); ?>
        <?php do_settings_sections( 'triggmine-settings-group' ); ?>
        <h3>Triggmine Settings</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Triggmine</th>
                <td>
                    <select name="triggmine_settings[plugin_enabled]">
                        <option value="1" <?php if ( $settings['plugin_enabled'] == 1 ) echo 'selected="selected"'; ?>>Yes</option>
                        <option value="0" <?php if ( $settings['plugin_enabled'] == 0 ) echo 'selected="selected"'; ?>>No</option>
                    </select>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">API URL <span class="tm_required">*</span></th>
                <td><input required type="text" name="triggmine_settings[api_url]" value="<?php echo $settings['api_url']; ?>" class="regular-text"/></td>
            </tr>
             
            <tr valign="top">
                <th scope="row">API key <span class="tm_required">*</span></th>
                <td><input required type="text" name="triggmine_settings[api_key]" value="<?php echo $settings['api_key']; ?>" class="regular-text"/></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
        
        </div>
        
        <div class="card">
        <h3>Export order history</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Order Export</th>
                <td>
                    <select name="triggmine_settings[order_export_enabled]">
                        <option value="1" <?php if ( $settings['order_export_enabled'] == 1 ) echo 'selected="selected"'; ?>>Yes</option>
                        <option value="0" <?php if ( $settings['order_export_enabled'] == 0 ) echo 'selected="selected"'; ?>>No</option>
                    </select>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Export date from</th>
                <td><input type="date" id="datepicker-from" name="triggmine_settings[order_export_date_from]" value="<?php echo $settings['order_export_date_from']; ?>" class="triggmine-datepicker" /></td>
            </tr>
             
            <tr valign="top">
                <th scope="row">Export date to</th>
                <td><input type="date" id="datepicker-to" name="triggmine_settings[order_export_date_to]" value="<?php echo $settings['order_export_date_to']; ?>" class="triggmine-datepicker" /></td>
            </tr>
        </table>
    
        </div>
        
        <div class="card">
        <h3>Export customer history</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Customer Export</th>
                <td>
                    <select name="triggmine_settings[customer_export_enabled]">
                        <option value="1" <?php if ( $settings['customer_export_enabled'] == 1 ) echo 'selected="selected"'; ?>>Yes</option>
                        <option value="0" <?php if ( $settings['customer_export_enabled'] == 0 ) echo 'selected="selected"'; ?>>No</option>
                    </select>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Export date from</th>
                <td><input type="date" id="datepicker-from" name="triggmine_settings[customer_export_date_from]" value="<?php echo $settings['customer_export_date_from']; ?>" class="triggmine-datepicker" /></td>
            </tr>
             
            <tr valign="top">
                <th scope="row">Export date to</th>
                <td><input type="date" id="datepicker-to" name="triggmine_settings[customer_export_date_to]" value="<?php echo $settings['customer_export_date_to']; ?>" class="triggmine-datepicker" /></td>
            </tr>
        </table>
    
        </div>
    </form>
    
    <?php } ?>
</div>