<?php 
class ControllerToolAddproduct extends Controller { 
	private $error = array();
	private $tempDir = 'addedit/';
	private $mainDir = 'data/product/';
	private $uploadPics = array();

	public function __destruct()
	{
		foreach ($this->uploadPics as $key => $pic)
		{
			@unlink($pic);
		}
	}

	public function index()
	{
		$this->load->language('tool/addproduct');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('tool/addproduct');

		$this->data['button_save']         = $this->language->get('button_save');
		$this->data['button_delete']       = $this->language->get('button_delete');
		$this->data['button_add']          = $this->language->get('button_add');
		$this->data['heading_title']       = $this->language->get('heading_title');
		$this->data['action']              = $this->url->link('tool/addproduct', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['addlink']             = $this->url->link('tool/addproduct/addProductAction', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['export']              = $this->url->link('tool/export/download', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['post_max_size']       = $this->return_bytes( ini_get('post_max_size') );
		$this->data['upload_max_filesize'] = $this->return_bytes( ini_get('upload_max_filesize') );
		$this->data['uploadpiclink']   	   = $this->url->link('tool/addproduct/uploadPic', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['editlink']            = '';
		$this->getFormData();
		
		// desision
			if(empty($this->request->get['product_id']))
			{
				// форма добавления
				$this->data['form_type'] = 'add';
				$product = $this->getEmptyProduct();
			}
			else
			{
				// форма редактирования
				$this->data['editlink'] = $this->url->link('tool/addproduct/editProductAction', 'token=' . $this->session->data['token'].'&product_id='.$this->request->get['product_id'], 'SSL');
				$this->data['form_type'] = 'edit';
				$this->data['product_id'] = intval($this->request->get['product_id']);
				$product = $this->getProduct($this->data['product_id']);
			}
			$this->data['product'] = $product;

		$this->getForm();
	}


	private function return_bytes($val)
	{
		$val = trim($val);
		switch (strtolower(substr($val, -1)))
		{
			case 'm': $val = (int)substr($val, 0, -1) * 1048576; break;
			case 'k': $val = (int)substr($val, 0, -1) * 1024; break;
			case 'g': $val = (int)substr($val, 0, -1) * 1073741824; break;
			case 'b':
				switch (strtolower(substr($val, -2, 1)))
				{
					case 'm': $val = (int)substr($val, 0, -2) * 1048576; break;
					case 'k': $val = (int)substr($val, 0, -2) * 1024; break;
					case 'g': $val = (int)substr($val, 0, -2) * 1073741824; break;
					default : break;
				} break;
			default: break;
		}
		return $val;
	}

	
	// добавление продукта
	public function addProductAction()
	{
		$this->load->model('catalog/product');
		$product_data = $this->validate();
		if($product_data)
		{
			$this->model_catalog_product->addProduct($product_data);
			$query = $this->db->query("SELECT MAX(product_id) as lid FROM `".DB_PREFIX."product`"); 
				$product_id = $query->row['lid'];
			echo json_encode(array('result'=>'ok', 'product_id'=>$product_id));
		}
		else
		{
			echo json_encode(array('result'=>'0', 'error'=>$this->error));
		}
	}

	// созранение продукта
	public function editProductAction()
	{
		if (($this->request->server['REQUEST_METHOD'] == 'POST')  && !empty($this->request->get['product_id']))
		{
			$product_data = $this->validate();
			if($product_data)
			{
				$this->load->model('catalog/product');
				$this->model_catalog_product->editProduct($this->request->get['product_id'], $product_data);
				echo json_encode(array('result'=>'ok'));
			}
			else
			{
				echo json_encode(array('result'=>'0', 'error'=>$this->error));
			}
		}
	}
	
	// валидация
	private function validate()
	{
		$product_post = $this->request->post['product'];
		$product_post['main_pic'] = (!empty($this->request->post['main_pic']))?$this->request->post['main_pic']:'';
		$product = array();
		if(empty($product_post['name']))
		{
			$this->error = 'Не заполнено название товара';
			return false;
		}
		if(empty($product_post['price']))
		{
			$this->error = 'Не указана цена';
			return false;
		}
		if(empty($product_post['url_code']))
		{
			$this->error = 'Не указан символьный код';
			return false;
		}
		if(empty($product_post['main_pic']))
		{
			$this->error = 'Не указана картинка товара';
			return false;
		}


		// MAIN PIC
			if(!empty($this->request->get['product_id']))
				$product_id = $this->request->get['product_id'];
			else
			{
				$query = $this->db->query("SELECT MAX(product_id) as lid FROM `".DB_PREFIX."product`"); 
				$product_id = $query->row['lid']+1;
			}
			$product_dir = $this->mainDir.$product_id.'/';
			if(!empty($product_post['main_pic']))
			{
				if(strpos($product_post['main_pic'], $this->tempDir) !== false)
				{
					$product_post['main_pic'] = $this->copyToDir($product_post['main_pic'],$product_dir);
				}	
			}

		$product['product_description'][1] = Array
		(
			'name'             => (!empty($product_post['name']))?$product_post['name']:'', 
			'seo_h1'           => '', 
			'seo_title'        => '', 
			'meta_keyword'     => (!empty($product_post['seo_keywords']))?$product_post['seo_keywords']:'', 
			'meta_description' => (!empty($product_post['seo_description']))?$product_post['seo_description']:'', 
			'description'      => (!empty($product_post['description']))?$product_post['description']:'', 
			'tag'              => '', 
		);
		$product['product_description'][2] = Array
		(
			'name'             => (!empty($product_post['name']))?$product_post['name']:'', 
			'seo_h1'           => '', 
			'seo_title'        => '', 
			'meta_keyword'     => (!empty($product_post['seo_keywords']))?$product_post['seo_keywords']:'', 
			'meta_description' => (!empty($product_post['seo_description']))?$product_post['seo_description']:'', 
			'description'      => (!empty($product_post['tao']))?$product_post['tao']:'', 
			'tag'              => '', 
		);
		$product['model']            = '';
		$product['sku']              = '';
		$product['upc']              = '';
		$product['ean']              = '';
		$product['jan']              = '';
		$product['isbn']             = '';
		$product['mpn']              = '';
		$product['location']         = '';
		$product['price']            = (!empty($product_post['price']))?$product_post['price']:'';
		$product['manufacturer_id']  = (!empty($product_post['manufacturer_id']))?$product_post['manufacturer_id']:'';
		$product['tax_class_id']     = 0;
		$product['quantity']         = 100;
		$product['minimum']          = 1;
		$product['subtract']         = 1;
		$product['stock_status_id']  = 8;
		$product['shipping']         = 1;
		$product['keyword']          = (!empty($product_post['url_code']))?$product_post['url_code']:'';
		$product['image']            = (!empty($product_post['main_pic']))?$product_post['main_pic']:'';
		$product['date_available']   = '2014-02-27';
		$product['length']           = 0;
		$product['width']            = 0;
		$product['height']           = 0;
		$product['length_class_id']  = 1;
		$product['weight']           = 0;
		$product['weight_class_id']  = 1;
		$product['status']           = 1;
		$product['sort_order']       = (!empty($product_post['sort_order']))?$product_post['sort_order']:'0';
		$product['main_category_id'] = (!empty($product_post['section']))?end($product_post['section']):'';
		$product['product_category'] = (!empty($product_post['section']))?$product_post['section']:array();
		$product['filter']           = '';
		$product['product_store']    = Array(0);
		$product['download']         = '';
		$product['points']         = '';
		$product['related']          = '';

		// options
		$product['product_option'] = Array();
			// SIZE
				$option_values = array();
				foreach($product_post['size'] as $size)
				{
					$option_values[] = array
					(
						'option_value_id'         => $size,
						'product_option_value_id' => '',
						'quantity'                => '100',
						'subtract'                => '1',
						'price_prefix'            => '+',
						'price'                   => 0,
						'points_prefix'           => '+',
						'points'                  => 0,
						'weight_prefix'           => '+',
						'weight'                  => 0
					);
				}
				$product['product_option'][0] = array
				(
					'product_option_id'    => (!empty($product_post['product_size_option_id']))?$product_post['product_size_option_id']:'',
					'name'                 => 'Размер',
					'option_id'            => (!empty($product_post['size_option_id']))?$product_post['size_option_id']:'',
					'type'                 => 'select',
					'required'             => '1',
					'product_option_value' => $option_values,
				);
			// COLORS
				// проверка нужно создавать новую опцию или нет
					if(empty($this->request->post['color_option_id']) && !empty($this->request->get['product_id']) && count($product_post['color_ids']))
					{
						// опция не указана, форма редактирования
						// ищем нужную опцию
						// Цвет#<id>
						$this->load->model('catalog/option');
						$this->load->model('tool/image');
						$data = array(
							'filter_name' => 'Цвет#'.$this->request->get['product_id'],
							'start'       => 0,
							'limit'       => 1
						);
						$options = $this->model_catalog_option->getOptions($data);
						if(!count($options))
						{
							// создание новой опции для цветов
							$_option = $this->cerateColorOption();
						}
						else
						{
							// опция создана
							$_option = $this->addValuesToOption($options[0]['option_id']);
						}
					}
					elseif(!empty($this->request->post['color_option_id']) && !empty($this->request->get['product_id']) && count($product_post['color_ids']))
					{
						// форма редакирования, опция создана и выбрана
						$_option = $this->addValuesToOption($this->request->post['color_option_id']);
					}
					elseif(count($product_post['color_ids']))
					{
						// создание новой опции с добавлением картинок
						$_option = $this->cerateColorOption();
					}

				$product['product_option'][1] = $_option;

		// product images
			$product['product_image'] = array();
			if(!empty($product_post['product_image']))
			{
				foreach ($product_post['product_image'] as $key => $_pimage)
				{
					$pic_url =  $_pimage['image'];
					if(strpos($pic_url, $this->tempDir) !== false)
					{
						$pic_url = $this->copyToDir($pic_url,$product_dir);
					}
					$_pimage['image'] = $pic_url;
					$product['product_image'][] = $_pimage;
				}
			}
		return $product;
	}

	// вывод формы
	private function getForm()
	{
		// Alerts
			if (isset($this->error['warning']))
			{
				$this->data['error_warning'] = $this->error['warning'];
			}
			else
			{
				$this->data['error_warning'] = '';
			}
			if (isset($this->session->data['success']))
			{
				$this->data['success'] = $this->session->data['success'];
				unset($this->session->data['success']);
			}
			else
			{
				$this->data['success'] = '';
			}
		
		// хлебные крошки
			$this->data['breadcrumbs'] = array();
			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => FALSE
			);
			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('tool/addproduct', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: '
			);
		
		// Render
			$this->template = 'tool/addproduct.tpl';
			$this->children = array(
				'common/header',
				'common/footer',
			);
			$this->response->setOutput($this->render());
	}

	// достает основную информацию формы (категории, размеры и тд)
	private function getFormData()
	{
		// все категории
			$this->load->model('catalog/category');
			$this->data['categories'] = array();
			foreach($this->model_catalog_category->getCategories(array()) as $category)
			{
				$this->data['categories'][] = array(
					'id'   => $category['category_id'],
					'name' => $category['name']
				);
			}
		// размеры
			$this->load->model('catalog/option');
			$this->load->model('tool/image');
			
			$data = array(
				'filter_name' => 'Размер',
				'start'       => 0,
				'limit'       => 20
			);
			$options = $this->model_catalog_option->getOptions($data);
			$option_values = $this->model_catalog_option->getOptionValues(14);

			foreach ($option_values as $option_value)
			{
				$option_value_data[] = array(
					'id' => $option_value['option_value_id'],
					'name'            => html_entity_decode($option_value['name'], ENT_QUOTES, 'UTF-8')
				);
			}
			$sort_order = array();
			foreach ($option_value_data as $key => $value)
			{
				$sort_order[$key] = $value['name'];
			}
			array_multisort($sort_order, SORT_ASC, $option_value_data);
			$this->data['sizes'] = $option_value_data;
		// get Brends
			$this->load->model('catalog/manufacturer');
	    	$this->data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();

	}

	private function getEmptyProduct()
	{
		return array
		(
			'section'                 => array(),
			'name'                    => '',
			'sort_order'              => '0',
			'url_code'                => '',
			'seo_keywords'            => '',
			'seo_description'         => '',
			'description'             => '',
			'price'                   => '',
			'manufacturer_id'         => '',
			'tao'                     => '',
			'main_pic'                => '',
			'color'                   => Array(),
			'color_url'               => Array(),
			'size_ids'                => Array(),
			'product_images'          => Array(),
			'product_size_option_id'  => '',
			'size_option_id'          => 14,
			'product_color_option_id' => '',
			'color_option_id'         => ''
		);
	}

	private function getProduct($product_id)
	{
		$this->load->model('catalog/product');
		$this->load->model('catalog/option');
		$this->load->model('tool/image');
		$this->load->model('catalog/category');
		$product_info = $this->model_catalog_product->getProduct($product_id);
	
		// MAIN IMAGE
			if(!empty($product_info) && $product_info['image'] && file_exists(DIR_IMAGE . $product_info['image']))
			{
				$_mimage['real']  = $product_info['image'];
				$_mimage['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
			}
			else
			{
				$_mimage['thumb'] = $this->model_tool_image->resize('no_image.jpg', 100, 100);
				$_mimage['real']  = '';
			}
		// CATEGORY
			$categories = $this->model_catalog_product->getProductCategories($product_id);
		// SIZES AND COLORS
			$product_options         = $this->model_catalog_product->getProductOptions($product_id);
			$colors                  = array();
			$colors_url              = array();
			$colors_ids              = array();
			$size_ids                = array();
			$product_size_option_id  = '';
			$size_option_id          = 14;
			$product_color_option_id = '';
			$color_option_id         = '';
			foreach ($product_options as &$product_option)
			{
				$_areColor = (strpos($product_option['name'], 'Цвет') !== false)?true:false;
				$_areSize  = (strpos($product_option['name'], 'Размер') !== false)?true:false;
				if($_areSize)
				{
					$product_size_option_id = $product_option['product_option_id'];
					$size_option_id         = $product_option['option_id'];
				}
				if($_areColor){
					$product_color_option_id = $product_option['product_option_id'];
					$color_option_id         = $product_option['option_id'];
				}
				// достаем занчения опций
				$_option_values = $this->model_catalog_option->getOptionValues($product_option['option_id']);
				foreach ($product_option['product_option_value'] as &$prod_val)
				{
					foreach($_option_values as $_option_value)
					{
						if($_option_value['option_value_id'] == $prod_val['option_value_id'])
						{
							$prod_val = array_merge($prod_val,$_option_value);
							if($_areColor)
							{
								$colors[] = $prod_val['name'];
								if(!empty($prod_val['image']) && file_exists(DIR_IMAGE . $prod_val['image']))
								{
									$_image['thumb'] = $this->model_tool_image->resize($prod_val['image'], 100, 100);
									$_image['real']  = $prod_val['image'];
								}
								else
								{
									$_image['thumb'] = $this->model_tool_image->resize('no_image.jpg', 100, 100);
									$_image['real']  = '';
								}
								$colors_url[] = $_image;
								$colors_ids[]   = $prod_val['option_value_id'];
							}
							break;
						}
						elseif($_areSize)
						{
							$size_ids[] = $prod_val['option_value_id'];
							break;
						}
					} 
				}
			}

		// DOP IMAGES
			$_product_images = $this->model_catalog_product->getProductImages($product_id);
			$product_images = array();
			foreach ($_product_images as $product_image)
			{
				if ($product_image['image'] && file_exists(DIR_IMAGE . $product_image['image']))
				{
					$image = $product_image['image'];
				}
				else
				{
					$image = 'no_image.jpg';
				}
				$product_images[] = array(
					'image'      => $image,
					'thumb'      => $this->model_tool_image->resize($image, 100, 100),
					'sort_order' => $product_image['sort_order']
				);
			}

		// get TAO LINK
			$query = $this->db->query("SELECT DISTINCT *, (SELECT keyword FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id . "') AS keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '2'");
			$tao_link = $query->row['description'];
		
		return array
		(
			'section'                 => $categories,
			'name'                    => $product_info['name'],
			'sort_order'              => $product_info['sort_order'],
			'url_code'                => $product_info['keyword'],
			'seo_keywords'            => $product_info['meta_keyword'],
			'seo_description'         => $product_info['meta_description'],
			'description'             => $product_info['description'],
			'price'                   => $product_info['price'],
			'manufacturer_id'         => $product_info['manufacturer_id'], 
			'tao'                     => $tao_link,
			'main_pic'                => $_mimage,
			'color'                   => $colors,
			'color_url'               => $colors_url,
			'color_ids'               => $colors_ids,
			'size_ids'                => $size_ids,
			'product_size_option_id'  => $product_size_option_id,
			'size_option_id'          => $size_option_id,
			'product_color_option_id' => $product_color_option_id,
			'color_option_id'         => $color_option_id,
			'product_images'          => $product_images
		);
	}

	private function uploadOnePic($file)
	{
		$error  = '';
		$result = array();
		if($file['error'] == UPLOAD_ERR_OK)
		{
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if (false === $ext = array_search(
				$finfo->file($file['tmp_name']),
				array(
					'jpg' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif',
				),
				true
			))
			{
				$error = 'Invalid file format.';
			}
			else
			{
				$picName = sprintf('%s.%s',sha1_file($file['tmp_name']),$ext);
				if(!move_uploaded_file($file['tmp_name'],DIR_IMAGE.$this->tempDir.$picName) )
				{
					$error = 'Cant upload';
				}
				else
				{
					$this->load->model('tool/image');
					$result['imagename'] = $this->tempDir.$picName;
					$result['thumb'] = $this->model_tool_image->resize($this->tempDir.$picName, 100, 100);
				}
			}
		}
		else
		{
			switch($file['error'])
			{
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					$error = 'No file sent.';
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$error = 'Exceeded filesize limit.';
				default:
					$error = 'Unknown errors.';
			}
		}
		return array('error'=>$error, 'result'=>$result);
	}

	public function uploadPic()
	{
		if(!is_dir(DIR_IMAGE.$this->tempDir))
			mkdir(DIR_IMAGE.$this->tempDir, 0777, true);
		$resArray = array();
		if(!empty($_FILES['pic']))
			$resArray = $this->uploadOnePic($_FILES['pic']);
		echo json_encode($resArray);
	}

	// создание опции цветов для товара
	// результат, массив который потом можно передать в функцию добавления товара
	private function cerateColorOption()
	{
		$this->load->model('catalog/option');
		if(!empty($this->request->get['product_id']))
			$product_id = $this->request->get['product_id'];
		else
		{
			$query = $this->db->query("SELECT MAX(product_id) as lid FROM `".DB_PREFIX."product`"); 
			$product_id = $query->row['lid']+1;
		}
		$option_values = array();
		$product_dir = $this->mainDir.$product_id.'/';
		foreach ($this->request->post['product']['color_ids'] as $key=>$color_id)
		{
			// перенос картинки в нужную папку
				$pic_url =  $this->request->post['product']['color_url'][$key];
				if(strpos($pic_url, $this->tempDir) !== false)
				{
					$pic_url = $this->copyToDir($pic_url,$product_dir);
				}
			$option_values[] = array
			(
				'option_value_id'          => '',
				'option_value_description' => Array( '1'=>array('name'=>$this->request->post['product']['color'][$key])),
				'image'                    => $pic_url,
				'sort_order'               => 0
			);
		}
		$_option_data = array
		(
			'option_description' => array('1'=>array('name' => 'Цвет#'.$product_id)),
			'type'               => 'select',
			'sort_order'         => 0,
			'option_value'       => $option_values
		);
		$this->model_catalog_option->addOption($_option_data);
		$query = $this->db->query("SELECT MAX(option_id) as lid FROM `".DB_PREFIX."option`"); 
		
		$optionId = $query->row['lid'];
		return $this->getOptionArray($optionId);
	}

	private function copyToDir($sourceImage,$toDir)
	{
		if(!is_dir(DIR_IMAGE.$toDir))
			mkdir(DIR_IMAGE.$toDir, 0777, true);
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (false === $ext = array_search(
			$finfo->file(DIR_IMAGE.$sourceImage),
			array(
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			),
			true
		))
		{
			return false;
		}
		else
		{
			$this->uploadPics[] = DIR_IMAGE.$sourceImage;
			$new_name = uniqid().'.'.$ext;
			copy(DIR_IMAGE.$sourceImage, DIR_IMAGE.$toDir.$new_name);
			$pic_url = $toDir.$new_name;
		}
		return $pic_url;
	}

	// добавляет значения к опции
	// результат, массив который потом можно передать в функцию добавления товара
	public function addValuesToOption($optionId)
	{
		$this->load->model('catalog/option');
		// если есть новые значения опции то, обновляем опцию, если нет то оставляем как есть
		if(!empty($this->request->post['product']['color_ids']))
		{
			$option_values = array();
			$has_new = false;
			$product_dir = $this->mainDir.$this->request->get['product_id'].'/';
			foreach ($this->request->post['product']['color_ids'] as $key=>$color_id)
			{
				$pic_url =  $this->request->post['product']['color_url'][$key];
				if(strpos($pic_url, $this->tempDir) !== false)
				{
					$pic_url = $this->copyToDir($pic_url,$product_dir);
				}
				if($color_id != 'new')
				{
					$option_values[] = array
					(
						'option_value_id'          => $color_id,
						'option_value_description' => Array( '1'=>array('name'=>$this->request->post['product']['color'][$key])),
						'image'                    => $pic_url,
						'sort_order'               => 0
					);
				}
				else
				{
					$has_new = true;
					$option_values[] = array
					(
						'option_value_id'          => '',
						'option_value_description' => Array( '1'=>array('name'=>$this->request->post['product']['color'][$key])),
						'image'                    => $pic_url,
						'sort_order'               => 0
					);
				}
			}
			$_option_data = array
			(
				'option_description' => array('1'=>array('name' => 'Цвет#'.$this->request->get['product_id'])),
				'type'               => 'select',
				'sort_order'         => 0,
				'option_value'       => $option_values
			);
			$this->model_catalog_option->editOption($optionId, $_option_data);

			// достаем значения обновленной/необнавленной опции для передачи
			return $this->getOptionArray($optionId);
		}
	}
	
	private function getOptionArray($optionId)
	{
		$this->load->model('catalog/option');
		$_retOption = array();
		$_option = $this->model_catalog_option->getOption($optionId);
		$_retOption['name']                 = $_option['name'];
		$_retOption['option_id']            = $_option['option_id'];
		$_retOption['type']                 = $_option['type'];
		$_retOption['required']             = 1;
		$_retOption['product_option_id']    = (!empty($product_post['product_color_option_id']))?$product_post['product_color_option_id']:'';
		$_retOption['product_option_value'] = array();
		$_optionValues = $this->model_catalog_option->getOptionValues($optionId);
		foreach ($_optionValues as $key => $_optionValue)
		{
			$_retOption['product_option_value'][] = array
			(
				'option_value_id'         => $_optionValue['option_value_id'],
				'product_option_value_id' => '',
				'quantity'                => 100,
				'subtract'                => 1,
				'price_prefix'            => '+',
				'price'                   => 0,
				'points_prefix'           => '+',
				'points'                  => 0,
				'weight_prefix'           => '+',
				'weight'                  => 0,
			);
		}
		return $_retOption;
	}
}
?>