<?php

add_action('init', 'my_init'); 

function my_init() {

		if(isset($_POST['start_date_me'])) {

			$product_cat = $_POST['product_category'];

			$params = array(
				'posts_per_page' => 3000,
				'post_type' => array('product', 'product_variation'),
			    'tax_query' => array(
			        'relation' => 'OR',
			        array(
			            'taxonomy' => 'product_cat',
			            'field' => 'name',
			            'terms' => $product_cat
			        )
			    ),
				'date_query' => array(
						array(
							'after' => $_POST['start_date_me'], 
							'before' => $_POST['end_date_me']
						),
					),
			);
			if(isset($_POST['best_me'])) {
				$params['posts_per_page'] = 5;
				$params['meta_key'] = 'total_sales';
				$params['orderby'] = 'meta_value_num';
			}
			
			$result = get_posts($params);

			// print_r($result);
			// NOT Necessary once I get the 'tax_query' in $params 
			/*
			$another_result = array();

			// print_r($product_cat . ": ");

			foreach($result as $item) {
				$cats = wp_get_post_terms($item -> ID, 'product_cat');
				$flag = 1;
				foreach($cats as $cat) {
					// print_r($cat->name.",");
					if($cat->name == $product_cat)
						$flag = 0;
				}
				// print_r($flag);
				// print_r("\n");
				if($flag == 0) 
					array_push($another_result, $item);
			}

			$result = $another_result; */
			// NOT Necessary once I get the 'tax_query' in $params

			// print_r($result);

			$product_objects = array();

			foreach($result as $item) {
				array_push($product_objects, wc_get_product($item -> ID));
			}
			
			// print_r($product_objects);

			if(isset($_POST['top_me'])) {
				$max_one = $product_objects[0];
				foreach($product_objects as $item) {
					if((float)$max_one -> get_average_rating() < (float)$item -> get_average_rating()) {
						$max_one = $item;
					}
				}
				$product_objects = array($max_one); 
			}

			$field = 'Feature Image,Product URL,Product Name'."\n";
			$file_name = $_POST['product_category'].'_'.$_POST['start_date_me'].'_To_'.$_POST['end_date_me'].'.csv'; 

			foreach($product_objects as $product_item) {
				$field .= wp_get_attachment_image_src( get_post_thumbnail_id( $product_item->post->ID ), 'single-post-thumbnail' )[0].",";
				$field .= $product_item -> get_permalink() . ',';
				$field .= '"'.$product_item -> get_title() . '"'.\n";
			}

			header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");
			header("Content-type: application/CSV");
			header("Content-Disposition: attachment; filename=$file_name");
			echo $field;			

			exit ;

		}
	
		add_action( 'admin_menu', 'my_plugin_menu' );

		wp_enqueue_script('jquery');
	
}


function my_plugin_menu() {
	add_menu_page( 'My Plugin Options', 'CSV export', 'manage_options', 'csv-exporter-custom', 'my_plugin_options' );
}


function my_plugin_options() {
	echo '<div class="wrap">';
	echo '<h1> CSV export : Save yur valuable data! </h1>';
	$category_list = get_terms(
		'product_cat', 
		array(
			'hide_empty' => 0
		)
	); 
	?>
<form id = "export-csv" method = "POST">
	<label for = "product-category">Product Category : </label>
	<select id = "product-category" name = "product_category">
		<?php 
		foreach($category_list as $category) {
			echo '<option value = "'.$category->name.'">'.$category -> name.'</option>';
		}
	$max_date = new DateTime('now'); 
	$interval = new DateInterval('P1D');
	$max_date = $max_date -> add($interval);
	 ?>
	</select>
	<br/>
	<div class = "form-section col-md-12">
		<div class = "form-group">
			<label for = "start_date" class = "col-md-2" >START : </label>
			<input type = "date" id = "start_date" name = "start_date_me"  max="<?php echo date('Y-m-d'); ?>" required/> <br/>
		</div>
		<div class = "form-group">
			<label for = "end_date" class = "col-md-2" >&nbsp;&nbsp; END : </label>
			<input type = "date" id = "end_date" name = "end_date_me" max="<?php echo $max_date -> format('Y-m-d'); ?>" value = "<?php echo date('Y-m-d'); ?>" required/> <br/>		
		</div>
		<div class = "form-group">
			<input type = "checkbox" id = "best" name = "best_me" />
			<label for = "best">Best Selling Products</label> <br/>
			<input type = "checkbox" id = "top" name = "top_me" />
			<label for = "top">Top Rated products</label>
		</div>
		<br/>
		<input type = "submit" id = "Go" value = "Download" class = "button button-primary"/>
	</div>
</form>

	<?php
	echo '</div>';
}

?>