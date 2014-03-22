<?=$header?>
<link rel="stylesheet" href="/admin/view/stylesheet/addproduct.css" />
<div id="content">
	<div class="breadcrumb">
		<?php
		foreach($breadcrumbs as $breadcrumb)
		{
			?><?=$breadcrumb['separator']?><a href="<?=$breadcrumb['href']?>"><?=$breadcrumb['text']?></a><?php
		}
		?>
	</div>
	<?php if ($error_warning)
	{
		?><div class="warning"><?=$error_warning?></div><?php
	}
	if($success)
	{
	  	?><div class="success"><?=$success?></div><?php
	}
	?>
	<div class="box">
		<div class="heading">
			<h1><img src="view/image/backup.png" alt="" /> <?=$heading_title?></h1>
			<div class="buttons">
				<a  onclick="AddEdit.send()" class="button"><span><?php
				if($form_type == 'edit')
					echo $button_save;
				else
					echo $button_add;

				?></span></a>
				<a class="button"><span><?=$button_delete?></span></a>
			</div>
		</div>
		<div class="content addprod">
			<form
				action="<?=$action?>"
				method="post"
				enctype="multipart/form-data" id="form" 
				data-addlink="<?=$addlink?>" 
				data-editlink="<?=$editlink?>"
				data-uploadpiclink="<?=$uploadpiclink?>"
			>

				<input type="hidden" name="form_type" value="<?=$form_type?>" />
				<table class="form">
					<tr class="section">
						<td colspan="2">
							<label>Раздел <span class="red">*</span></label>
							<select name="product[section][]" id="section" multiple="true">
								<?php
								foreach ($categories as $category)
								{
									$slected = (in_array($category['id'],$product['section']))?'selected="selected"':'';
									?><option <?=$slected?> value="<?=$category['id']?>"><?=$category['name']?></option><?php
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Порядок сортировки </label>
							<input type="text" name="product[sort_order]" required="required" value="<?=$product['sort_order']?>" />
						</td>
					</tr>
					<tr class="section">
						<td colspan="2">
							<label>Производитель</label>
							<select name="product[manufacturer_id]" >
								<option value="0" selected="selected"> --- Не выбрано --- </option>
								<?php
								foreach ($manufacturers as $manufacturer)
								{
									$slected = ($manufacturer['manufacturer_id'] == $product['manufacturer_id'])?'selected="selected"':'';
									?><option <?=$slected?> value="<?=$manufacturer['manufacturer_id']?>"><?=$manufacturer['name']?></option><?php
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Название товара <span class="red">*</span></label>
							<input type="text" name="product[name]" required="required" value="<?=$product['name']?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Символьный код <span class="red">*</span></label>
							<input type="text" name="product[url_code]" required="required" value="<?=$product['url_code']?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Keywords(SEO)</label>
							<textarea cols="50" rows="3" name="product[seo_keywords]"><?=$product['seo_keywords']?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Description(SEO)</label>
							<textarea cols="50" rows="3" name="product[seo_description]"><?=$product['seo_description']?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Описание</label>
							<textarea cols="50" rows="3" name="product[description]"><?=$product['description']?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Цена (рубли/доллары)<span class="red">*</span></label>
							<input onkeyup="AddEdit.changeRubles(this);" type="text" name="rubles" style="width:212px;margin-right:10px;" required="required" value="<?=$product['price']*35.18?>" />
							<input onkeyup="AddEdit.changeDollars(this);" type="text" name="product[price]" style="width:212px;"  required="required" value="<?=$product['price']?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>Ссылки на TAO</label>
							<textarea type="text" name="product[tao]"  required="required" /><?=$product['tao']?></textarea>
						</td>
					</tr>
					<tr class="mainpic">
						<td colspan="2">
							<label>Основная картинка <span class="red">*</span></label>
							<div class="file_prev"><?php
							if(!empty($product['main_pic']))
							{
								?><img src="<?=$product['main_pic']['thumb']?>" alt=""/><?php
							}
							?></div>
							<div class="file_box mainpic">
								<input type="file" name="main_pic_file" />
								<input type="hidden" name="main_pic" value="<?=$product['main_pic']['real']?>" />
							</div>
						</td>
					</tr>
					<tr class="sizes">
						<td colspan="2">
							<label>Размеры</label>
							<select multiple name="product[size][]">
								<?php
								foreach($sizes as $key => $size)
								{
									$selected = (in_array($size['id'], $product['size_ids']))?'selected="selected"':'';
									?><option <?=$selected?> value="<?=$size['id']?>"><?=$size['name']?></option><?php
								}
								?>
							</select>
							<input type="hidden" name="product[product_size_option_id]" value="<?=$product['product_size_option_id']?>" />
							<input type="hidden" name="product[size_option_id]" value="<?=$product['size_option_id']?>" />
						</td>
					</tr>
					<tr class="dopimages colors">
						<td colspan="2">
							<label> Дополнительные картинки </label>
							<div class="file_prev">
								<?php
								foreach ($product['product_images'] as $key => $image)
								{
									?><div class="color" id="dopimg_<?=$key?>">
										<div class="img">
											<img src="<?=$image['thumb']?>" alt="" />
										</div>
										<div class="inp">
											<input type="text" name="product[product_image][<?=$key?>][sort_order]" placeholder="Сортировка" value="<?=$image['sort_order']?>" />
											<input type="hidden" name="product[product_image][<?=$key?>][image]" value="<?=$image['image']?>" />
										</div>
										<div class="btns">
											<button onclick="AddEdit.deleteColor(event,this);" class="delete">Удалить</button>
										</div>
									</div><?php
								}
								?>
							</div>
							<div class="file_box colorsbox">
								<input type="file" name="dopimages" />
							</div>
						</td>
					</tr>
					<tr class="colorstr colors">
						<td colspan="2">
							<label> Цвета (просто перенести сюда картинки) </label>
							<div class="file_prev">
								<?php
								foreach ($product['color'] as $key => $color)
								{
									?><div class="color">
										<div class="img">
											<img src="<?=$product['color_url'][$key]['thumb']?>" alt="" />
										</div>
										<div class="inp">
											<input type="text" name="product[color][]" placeholder="Цвет" value="<?=$color?>" />
											<input type="hidden" name="product[color_url][]" value="<?=$product['color_url'][$key]['real']?>" />
											<input type="hidden" name="product[color_ids][]" value="<?=$product['color_ids'][$key]?>" />
										</div>
										<div class="btns">
											<button onclick="AddEdit.deleteColor(event,this);" class="delete">Удалить</button>
										</div>
									</div><?php
								}
								?>
							</div>
							<div class="file_box colorsbox">
								<input type="file" name="colorfile" />
								<input type="hidden" name="product_color_option_id" value="<?=$product['product_color_option_id']?>"/>
								<input type="hidden" name="color_option_id" value="<?=$product['color_option_id']?>"/>
							</div>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
<script src="/admin/view/javascript/addproduct/init.js"></script>
<?=$footer?>