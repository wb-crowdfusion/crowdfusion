var MetaLabelTools = function() {

	jQuery.fn.addNewWindow = function() {

		if(!this.is(':text[name^=#]')) return this;

		var $this = this;
		var label = this.parent().prev('label');

		if(label.length == 1) {

			var labelText = label.text();

			var inject = function() {
				var val = $this.val();

				if($.trim(val).length == 0)
					return;

				label.empty();
				label.append(labelText+' ');

				//add new window link
				label.append('<a href="'+val+'" target="_blank">[NEW WINDOW]</a>');

			};

			setTimeout(inject,2000);

			this.change(inject);
		}

	};
	jQuery.fn.addAmazonSearch = function() {

		if(!this.is(':text[name^=#]')) return this;

		var $this = this;
		var label = this.parent().prev('label');

		if(label.length == 1) {

			var labelText = label.text();

			var inject = function() {
				var val = $this.val();

				if($.trim(val).length == 0)
					return;

				label.empty();
				label.append(labelText+' ');

				//add amazon search link
				label.append($('<a href="http://www.amazon.com/s?url=search-alias%3Daps&field-keywords='+val+'" target="_blank">[AMAZON SEARCH]</a>'));

			};

			setTimeout(inject,2000);

			this.change(inject);
		}

	};
    jQuery.fn.addWordCount = function() {

        if(!this.is(':text[name^=#]')) return this;

        var $this = this;
        var label = this.parent().prev('label');

        if(label.length == 1) {

            var labelText = label.text();

            var span = $('<span class="wordcount"><strong>0</strong> words</span>');

            var inject = function() {
                label.empty();
                label.append(labelText+' ');

                label.append(span);

                var upd = function(){
                    var val = $this.val();
                    var cnt = 0;

                    if(val.length > 0)
                        cnt = val.replace(/(<([^>]+)>)/ig,"").split(' ').length;

                    $('strong',span).text(cnt);
                };

                var t = null;
                $this.keyup(function(){
                    if(t != null)
                        clearTimeout(t);
                    t = setTimeout(upd,5000);
                });

                upd();
            };

            setTimeout(inject,2000);
        }
    };
    jQuery.fn.addCharCount = function() {

        if(!this.is(':text[name^=#]')) return this;

        var $this = this;
        var label = this.parent().prev('label');

        if(label.length == 1) {

            var labelText = label.text();

            var span = $('<span class="charcount"><strong>0</strong> characters</span>');

            var inject = function() {
                label.empty();
                label.append(labelText+' ');

                label.append(span);

                var upd = function(){
                     $('strong',span).text($this.val().length);
                };

                var t = null;
                $this.keyup(function(){
                    if(t != null)
                        clearTimeout(t);
                    t = setTimeout(upd,200);
                });

                upd();
            };

            setTimeout(inject,200);
        }
    };
}();