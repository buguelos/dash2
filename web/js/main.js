$(function() {
	$('.file-upload').each(function() {
		var element = this;

		var myDropzone = new Dropzone(element, {
			url: element.getAttribute('data-upload-url'),
			acceptedFiles: element.getAttribute('data-upload-accepted'),
			paramName : "file",
			maxFilesize : 32, // MB
			autoProcessQueue : true,
			success: function(file, response) {
				this.removeFile(file);
                var form = $(element).parents('form')[0];
                if (form) {
                    form.submit();
                } else {
                    window.location.reload();
                }
			},
			error: function(file, response) {
				alert(response);
				this.removeFile(file);
			},
			fallback: function(file) {
				$('.info', element).remove();
			}
		});

		$('.info', element).click(function() {
			$(element).click();
		});
	});

	$('[data-href]').each(function () {
		var elem = this;
		$(elem).css('cursor', 'pointer');
		$(this).click(function () {
			window.location = elem.getAttribute('data-href');
		});
	});
	
});
