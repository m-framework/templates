if (typeof m == 'undefined') {
    var m = function () {};
    m.fn = m.prototype = {};
}

m.fn.upload_template = function() {

    var
        file_input = this,
        upload_text = file_input.next('.upload-text');

    file_input.on('change', function(){

        var file = this.files['0'];

        var
            _upload_text = upload_text.text(),
            formData = new FormData();

        formData.append('template', file);

        m.ajax({
            url: window.location.href,
            contentType: false,
            data: formData,
            success: function (data) {

                file_input.val('');

                if (typeof data.error !== 'undefined') {
                    upload_text.class({error: true, success: null});
                    upload_text.html(String(data.error));
                }
                else if(typeof data.success !== 'undefined') {
                    upload_text.class({error: null, success: true});
                    upload_text.html(data.success);
                }
                else {
                    upload_text.html(_upload_text);
                }
            }
        });
    });
};