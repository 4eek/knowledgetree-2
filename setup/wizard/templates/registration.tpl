<form id="registration" action="index.php?step_name=<?php echo $step_name; ?>" method="post" onsubmit="w.validateRegistration();return false;">
	<p class="title">Registering KnowledgeTree</p>
	
	<p class="description">
	Register with KnowledgeTree to receive important product updates. <b>We respect your privacy and will not share your information with third parties. For more information, please refer to our Privacy and Data Retention Policies.</b>
	<a href="index.php?step_name=install">Skip Registration</a>
	</p>
	<!-- Hidden Fields -->
	<input type="hidden" id="sendAll" name="" value="" />
	<input type="hidden" id="state" name="" />
	<p class='disclaimer'>
	We will not share your information with 3rd-parties, nor will we send you information not directly related to KnowledgeTree's products
	and services. Please see our <a href="http://www.knowledgetree.com/about/legal" target="_blank">Privacy and Data Retention policies</a> for more information.
	</p>
	<br/>
	<div id="step_content_registration" class="step">
		<span class="error" id="reg_error"></span>
		<?php $input_width = 32; ?>
		<table>
		    <tr>
		        <td><label for='first'>First Name</label></td>
		        <td rowspan='6' width='5%'>&nbsp;</td>
		        <td><input name='submitted[first_name]' id='first' size='<?php echo $input_width; ?>' style="float:left;"/></td>
		        <td rowspan='6' width='5%'>&nbsp;</td>
		        <td rowspan='6'> <img src='resources/graphics/dropbox.png' /> </td>
		    </tr>
		    
		    <tr>
		        <td><label for='last'>Last Name</label></td>
		        <td><input name='submitted[last_name]' id='last' size='<?php echo $input_width; ?>' style="float:left;"/></td>
		    </tr>
		    <tr>
		        <td><label for='email'>Email Address</label></td>
		        <td><input name='submitted[email_address]' id='email' size='<?php echo $input_width; ?>' style="float:left;"/></td>
		    </tr>
		    <tr>
		        <td><label for='country'>Country</label></td>
		        <td>
		            <select id='country' name='submitted[country]'>
		                <?php
		                    $str = '';
		                    foreach ($countries as $code => $country) {
		                        $str .= "<option name='{$code}' value='{$country}'>{$country}</option>";
		                    }
		
		                    echo $str;
		                ?>
		            </select>
		        </td>
		    </tr>
		    <tr>
		        <td><label for='industry'>Industry</label></td>
		        <td>
		            <select id='industry' name='submitted[industry]'>
		                <?php
		                    $str = '';
		                    foreach ($industries as $code => $industry) {
		                        $str .= "<option name='{$code}' value='{$industry}'>{$industry}</option>";
		                    }
		
		                    echo $str;
		                ?>
		            </select>
		        </td>
		    </tr>
		    <tr>
		        <td><label for='size'>Organization Size</label></td>
		        <td>
		            <select id='size' name='submitted[organization_size]'>
		                <?php
		                    $str = '';
		                    foreach ($org_size as $code => $size) {
		                        $str .= "<option name='{$code}' value='{$size}'>{$size}</option>";
		                    }
		
		                    echo $str;
		                ?>
		            </select>
		        </td>
		    </tr>
		</table>
		
		<input type='hidden' name='form_id' value='webform_client_form_242' />
		<input type='hidden' name='op' value='Submit' />
	</div>
	
	<input type="submit" name="Previous" value="Previous" onclick="w.pClick()" class="button_previous"/>
	<input type="submit" name="Next" value="Register" onclick="w.nClick()" class="button_next"/>
</form>