<div class="wrap">
    <?php screen_icon( 'options-general' ); ?>
    <h2><?php echo Synthesis_Site_Sensor::$plugin_name; ?> Settings</h2>
    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label>Site Sensor Key</label>
                </th>
                <td>
                    <input name="ssc-url" type="text" value="<?php echo $ssc_url; ?>" class="regular-text code" />
                    <p class="description">This plugin requires an active <a href="http://websynthesis.com/site-sensor/"><?php echo Synthesis_Site_Sensor::$plugin_name; ?></a> account.  Upon signup, you will be provided with a key to activate Site Sensor on your WordPress site.</p>  
                    <p class="description">Visit the support tab in <a href="http://sitesensor.websynthesis.com/login/">your account</a> and download our User Guide for information about check types, WordPress specific checks, and overall service configuration.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Monitoring Options</th>
                <td>
                    <div>
                        <i>Select the items you'd like Site Sensor to monitor. Unchecked items will always show as green.</i>
                    </div>
                    <div>
                        <input type="checkbox" <?php checked( $sense_wp_version ); ?> name="sense-wp-version"> WordPress Version</input>
                    </div>
                    <div>
                        <input type="checkbox" <?php checked( $sense_privacy_settings ); ?> name="sense-privacy-settings"> WordPress Privacy Settings</input>
                    </div>
                    <div>
                        <input type="checkbox" <?php checked( $sense_rss_feeds ); ?> name="sense-rss-feeds"> RSS Feed</input>
                    </div>
                    <div>
                        <input type="checkbox" <?php checked( $sense_xml_sitemap ); ?> name="sense-xml-sitemap"> XML Sitemap</input>
                    </div>
                </td>
            </tr>
        </table>

        <?php wp_nonce_field( 'save', Synthesis_Site_Sensor::SETTINGS_NONCE, false )?>
        <p class="submit">
            <input class="button-primary" name="ssc-save" type="submit" value="Save Settings">
        </p>
    </form>
</div>
