var AddEdit = 
{
	'init':function()
	{
		H.Files.init('.mainpic .file_box', this.mainPicDroped, 'input[name="main_pic_file"]');
		H.Files.init('.dopimages .file_box', this.dopImagesDroped, 'input[name="dopimages"]');
		H.Files.init('.colorstr .file_box', this.colorsDroped, 'input[name="colorfile"]');
	},
	'send':function()
	{
		var _serialized = $('#form').serialize();
		var _action     = $('#form').data('addlink');
		if($('input[name="form_type"]').val() == 'edit')
			_action     = $('#form').data('editlink');

		$.ajax({
			type: "POST",
			url: _action,
			data: _serialized,
			dataType:'json'
		})
		.done(function(msg)
		{
			$('.success').fadeOut();
			$('.warning').fadeOut();
			if(typeof msg.product_id != 'undefined')
			{
				$('.box').before('<div class="success">Товар добвлен</div>');
				window.location = window.location.href+'&product_id='+msg.product_id;
			}
			else if(msg.result == 'ok')
			{
				$('.box').before('<div class="success">Изменения сохранены </div>');
			}
			else
			{
				$('.box').before('<div class="warning">'+msg.error+'</div>');
			}
		});
	},
	'deleteColor':function(event,instance)
	{
		$(instance).parents('.color').remove();
		event.preventDefault();
		return false;
	},
	'mainPicDroped':function(file)
	{
		var _uploadmainpiclink     = $('#form').data('uploadpiclink');
		if(file.length > 1)
		{
			file = [file[0]];
		}

		if(file.length == 1)
		{
			// проверка формата фалй
			var tFile = file[0];
			if(!H.Files.isImage(tFile))
				alert('Не картинка');
			else if(tFile.size > 41943040)
			{
				alert('Большой файл');
			}
			else
			{
				// upload
				console.log(tFile);
				AddEdit.uploadPic(tFile,function(mp)
				{
					// returned pic address
					if(mp.error != '')
						console.log(mp.error);
					else
					{
						$('.mainpic .file_prev').html('<img src="'+mp.result.thumb+'"/>');
						$('.mainpic .file_box input[name="main_pic"]').val(mp.result.imagename);
					}
					console.log(mp);
				},_uploadmainpiclink);
			}
				
		}
	},
	'dopImagesDroped':function(file)
	{
		var _uploaddopimageslink     = $('#form').data('uploadpiclink');
		// оставляем только картинки
			var tFile = file;
			tFileLength = tFile.length;
			for(var i = 0; i < tFileLength; i++)
			{
				if(!H.Files.isImage(tFile[i]))
					tFile.splice(i,1);
				if(tFile[i].size > 41943040)
					tFile.splice(i,1);
			}

		if(tFile.length > 0)
		{
			$.each(tFile,function(i,apic)
			{
				// upload
				AddEdit.uploadPic(apic,function(mp)
				{
					// returned pic address
					if(mp.error != '')
						console.log(mp.error);
					else
					{
						var _key = $('.dopimages .color').size();
						$('.dopimages .file_prev').append('<div class="color"><div class="img"><img src="'+mp.result.thumb+'" alt=""></div><div class="inp"><input type="text" name="product[product_image]['+_key+'][sort_order]" placeholder="Сортировка" value="0" /><input type="hidden" name="product[product_image]['+_key+'][image]" value="'+mp.result.imagename+'"></div><div class="btns"><button onclick="AddEdit.deleteColor(event,this);" class="delete">Удалить</button></div></div>');
					}
				},_uploaddopimageslink);
			});
		}
		else
		{
			alert('Файлы не корректны');
		}
	},
	'colorsDroped':function(file)
	{
		var _uploaddopimageslink     = $('#form').data('uploadpiclink');
		// оставляем только картинки
			var tFile = file;
			tFileLength = tFile.length;
			for(var i = 0; i < tFileLength; i++)
			{
				if(!H.Files.isImage(tFile[i]))
					tFile.splice(i,1);
				if(tFile[i].size > 41943040)
					tFile.splice(i,1);
			}

		if(tFile.length > 0)
		{
			$.each(tFile,function(i,apic)
			{
				// upload
				AddEdit.uploadPic(apic,function(mp)
				{
					// returned pic address
					if(mp.error != '')
						console.log(mp.error);
					else
					{
						$('.colorstr .file_prev').append('<div class="color"> <div class="img"> <img src="'+mp.result.thumb+'" alt=""> </div> <div class="inp"> <input type="text" name="product[color][]" placeholder="Цвет" /> <input type="hidden" name="product[color_url][]" value="'+mp.result.imagename+'"> <input type="hidden" name="product[color_ids][]" value="new" /> </div> <div class="btns"> <button onclick="AddEdit.deleteColor(event,this);" class="delete">Удалить</button> </div> </div>');
					}
				},_uploaddopimageslink);
			});
		}
		else
		{
			alert('Файлы не корректны');
		}
	},
	'uploadPic':function(pic, callback, action)
	{
		var formData = new FormData();
		formData.append("pic", pic);
		$.ajax({
			url: action,
			type: 'POST',
			xhr: function() {  // custom xhr
				myXhr = $.ajaxSettings.xhr();
				if(myXhr.upload){}
				return myXhr;
			},
			success: callback,
			error: function(e)
			{
				console.log('error uploadng Pics');
			},
			data: formData,
			cache: false,
			contentType: false,
			dataType:'json',
			processData: false
		});
	},
	'changeRubles':function(instance)
	{
		var newVal = $(instance).val()/35.18;
		$('input[name="product[price]"]').val(newVal.toFixed(4));
	},
	'changeDollars':function(instance)
	{
		var newVal = $(instance).val()*35.18;
		$('input[name="rubles"]').val(newVal.toFixed(4));
	}
}

// DRAG DROP
var H = {};
H.Files = 
{
	'errorTpl':'<div class="fmsg file_error">{msg}</div>',
	'warningTpl':'<div class="fmsg file_warning">{msg}</div>',
	'mediaTpl':'<div id="{id}" class="upled_file"><span class="img"><img src="{thumb}" /></span><span class="name">{filename}</span><span class="status">{status}</span><span onclick="{removecall}" class="close"></span></div>',
	'init':function(box, callback, fileInput)
	{
		var self = this;
		$(box).on('dragleave', function(event){ self.boxHover(event); });
		$(box).on('dragover', function(event){ self.boxHover(event); });
		$(box).on('drop', function(event){ callback(self.boxDrop(event)); });
		$(fileInput).on('change', function(event){ callback(self.boxDrop(event,true)); });
	},
	'_clearMsgs':function(box)
	{
		$(box).siblings('.fmsg').remove();
	},
	'error':function(box,msg)
	{
		this._clearMsgs(box);
		$(box).before(this.errorTpl.replace('{msg}',msg));
	},
	'warning':function(box,msg)
	{
		this._clearMsgs(box);
		$(box).before(this.warningTpl.replace('{msg}',msg));
	},
	'boxHover':function(event)
	{
		event.stopPropagation();
        event.preventDefault();
        event.currentTarget.className = (event.type == "dragover" ? "file_box hover" : "file_box");
	},
	'boxDrop':function(event, fIonput)
	{
		fIonput = (typeof fIonput != 'undefined')?true:false;
		if(!fIonput)
			H.Files.boxHover(event);
		var files = event.originalEvent.target.files || event.originalEvent.dataTransfer.files;
		return files;
	},
	'isImage':function(file)
	{
		// png, jpeg, bnp
		if(file.type == 'image/png' || file.type == 'image/jpeg' || file.type == 'image/bnp')
			return true;
		else
			return false;
	},
	// return false or image local path
	'_path':false,
	'_setCall':function(file, callback)
	{
		var reader = new FileReader();
		H.Files._path = false;
		reader.onload = callback;
		reader.readAsDataURL(file);
	}
}


$(document).ready(function()
{
	AddEdit.init();
});