/* String Prototype additions */

String.prototype.lTrim = function (char) {
	if(char != null)
		return this.replace(new RegExp('^'+char+'*'), "");
	else
		return this.replace(/^\s*/, "");
};
String.prototype.rTrim = function (char) {
if(char != null)
	return this.replace(new RegExp(char+'*$'), "");
else
	return this.replace(/\s*$/, "");

};
String.prototype.trim = function (char) {return this.rTrim(char).lTrim(char);};
String.prototype.endsWith = function(sEnd) {return (this.substr(this.length-sEnd.length)==sEnd);};
String.prototype.startsWith = function(sStart) {return (this.substr(0,sStart.length)==sStart);};
String.prototype.format = function() {
	var s = this;
	for (var i=0; i < arguments.length; i++){
		s = s.replace("{" + (i) + "}", arguments[i]);
	}
	return(s);
};
String.prototype.ucFirst = function() {return this.substr(0,1).toUpperCase()+this.substr(1,this.length).toLowerCase();};

String.prototype.encode = function() {
	var s = ''+this;
	return $('<div></div>').text(s).html();
};

String.prototype.removeSpaces = function() {return this.replace(/ /gi,'');};
String.prototype.onlyNum = function() {	return this.replace(/[^\d\-]/g,'');};
String.prototype.onlyPositiveNum = function() {	return this.replace(/[^\d]/g,'');};

String.prototype.stripHtml = function() { return this	// unConvert HTML entitites after stripping tags
												.replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'')
												.replace(/&quot;/gi, '"')
												.replace(/&amp;/gi, '&')
												.replace(/&lt;/gi, '<')
												.replace(/&gt;/gi, '>')
												.replace(/&nbsp;/gi, ' '); };
String.prototype.wordCount = function() {
	var m = this.trim().match(/[\[\{\(<"]?([\$0-9A-Za-z\-\.]+)(?:[\>>\s;,\)\]\}"':!\?]|$)+/g); // This word count is slightly more accurate than php's.
	if (m) {
		return m.length;
	} else {
	 	return 0;
	}
};

String.prototype.pluralize = function() {

	var plural;
	var lastChar = this.charAt(this.length-1);
	if(lastChar != 's' && lastChar != 'S') {
		if(lastChar == 'y' || lastChar == 'Y')
			plural = this.substr(0,this.length-1) + 'ies';
		else
			plural = this + 's';
	} else {
		plural = this;
	}

	return plural;
};

function array_inject(array, index, value) {

	return array.concat(array.slice(0, index).push(value), array.slice(index+1));

};


var Interface = function(name, methods) {
    if(arguments.length != 2) {
        throw new Error("Interface constructor called with " + arguments.length
          + "arguments, but expected exactly 2.");
    }

    this.name = name;
    this.methods = [];
    for(var i = 0, len = methods.length; i < len; i++) {
        if(typeof methods[i] !== 'string') {
            throw new Error("Interface constructor expects method names to be "
              + "passed in as a string.");
        }
        this.methods.push(methods[i]);
    }
};

// Static class method.

Interface.ensureImplements = function(object) {
    if(arguments.length < 2) {
        throw new Error("Function Interface.ensureImplements called with " +
          arguments.length  + "arguments, but expected at least 2.");
    }

    for(var i = 1, len = arguments.length; i < len; i++) {
        var interface = arguments[i];
        if(interface.constructor !== Interface) {
            throw new Error("Function Interface.ensureImplements expects arguments "
              + "two and above to be instances of Interface.");
        }

        for(var j = 0, methodsLen = interface.methods.length; j < methodsLen; j++) {
            var method = interface.methods[j];
            if(!object[method] || typeof object[method] !== 'function') {
                throw new Error("Function Interface.ensureImplements: object "
                  + "does not implement the " + interface.name
                  + " interface. Method " + method + " was not found.");
            }
        }
    }
};

function clone(object) {
    function F() {}
    F.prototype = object;
    return new F;
}
/* Augment function, improved. */

function augment(receivingClass, givingClass) {
  if(arguments[2]) { // Only give certain methods.
    for(var i = 2, len = arguments.length; i < len; i++) {
      receivingClass.prototype[arguments[i]] = givingClass.prototype[arguments[i]];
    }
  }
  else { // Give all methods.
    for(methodName in givingClass.prototype) {
      if(!receivingClass.prototype[methodName]) {
        receivingClass.prototype[methodName] = givingClass.prototype[methodName];
      }
    }
  }
}

// JS tools functions


function extend(subClass, superClass) {
	var F = function() {};
	F.prototype = superClass.prototype;
	subClass.prototype = new F();
	subClass.prototype.constructor = subClass;

	subClass.superclass = superClass.prototype;
	if(superClass.prototype.constructor == Object.prototype.constructor) {
		superClass.prototype.constructor = superClass;
	}
};

function decorate(targetClass, decoratorClass) {
	if(!targetClass || !decoratorClass) return;

    if(typeof targetClass.prototype._constructors == 'undefined')
        targetClass.prototype._constructors = new Array();

	for(var methodName in decoratorClass.prototype) {
        if(methodName == '__construct') {
            targetClass.prototype._constructors.push(decoratorClass.prototype[methodName]);
        }
		else if(!targetClass.prototype[methodName]) {
			targetClass.prototype[methodName] = decoratorClass.prototype[methodName];
		}
	}
};

function instanceOf(object,targetClass) {

	if(!object || !targetClass) return;
	for(var methodName in targetClass.prototype) {
		if(typeof object[methodName] == 'function' && !(methodName in object)) {
			return false;
		}
	}

	return true;
};

function isKeyCodeAlphaNumeric(keycode) {

	//KEYCODE DOESN'T ACCOUNT FOR THE SHIFT-KEY, IT'S THE RAW KEYBOARD CODE

	if(keycode >= 64 && keycode <= 90) return true; /* A-Z */
	if(keycode >= 48 && keycode <= 57) return true; /* 0-9 */

	//TODO: handle international keys
};

function isKeyCodePunctuation(keycode) {

	//TODO: implement if needed
};

// slug functions

function console_log(l) {
	if(typeof console != 'undefined')
		console.log(l);
};

function preventDefault(event) {
    if(event.preventDefault)
        event.preventDefault();
    else
        event.returnValue = false;
};

function stopPropagation(event) {
    if(event.stopPropagation)
        event.stopPropagation();
    else
        event.cancelBubble = true;
};

/*
function flashDebug(str, showAlert) {
	if (showAlert) alert(str);
	else console.log(str);
};

function addImages(list, date, target) {
	var editor = null;
	if(!target.match(/^dom_/)) {
		editor = FCKeditorAPI.GetInstance(target);
	}

	var siteid = $("#SiteID").val();
	var host = window.location.host.match(/([^\.]+)\.[^\.]+$/);
	var url = 'http://'+document.sitelist[siteid].MediaDomain+'/';

	list = list.replace(/ /g, "-").replace(/[\&\+\']+/g, "").toLowerCase();
	var images = list.split("|");
	var dateParts = date.split("-");
	var baseUrl = url+"media/"+dateParts[0]+"/"+dateParts[1]+"/"+dateParts[2]+"/";
	for (var i=0; i < images.length; i++) {
		var img = baseUrl+images[i];
		if(editor == null) {
			$('#URL').val(img);
			setTimeout(function() { $('#'+target.replace(/^dom_/, '')).attr('src', img); },1000);
		} else {
			setTimeout(function() { editor.InsertHtml("<img src='"+img+"' border='0' hspace='4' vspace='4'/>") },4000);
		}
	}

};
*/

jQuery.fn.inputFocus = function() {

	this.bind('focus', function() {
		window.inputHasFocus = true;
	}).bind('blur', function() {
		window.inputHasFocus = false;
	});

	return this;
};

function copy(obj) {
    if (typeof obj !== 'object' || obj == null) {
        return obj;
    }

    var c = obj instanceof Array ? [] : {};

    for (var i in obj) {
        var prop = obj[i];

        if (typeof prop == 'object') {
           if (prop instanceof Array) {
               c[i] = [];

               for (var j = 0; j < prop.length; j++) {
                   if (typeof prop[j] != 'object') {
                       c[i].push(prop[j]);
                   } else {
                       c[i].push(copy(prop[j]));
                   }
               }
           } else {
               c[i] = copy(prop);
           }
        } else {
           c[i] = prop;
        }
    }

    return c;
}

function humanFilesize(bytes, decimal) {

    if (typeof bytes == 'number') {
        var position = 0;
        var units = [
            " Bytes",
            " KB",
            " MB",
            " GB",
            " TB"
        ];
        while (bytes >= 1024 && (bytes / 1024) >= 1) {
            bytes /= 1024;
            position++;
        }

        if(typeof decimal == 'number')
            //return Math.floor(bytes) + '.' + (Math.round((bytes - Math.floor(bytes)) * Math.pow(10,decimal))) + units[position];
            return bytes.toFixed(decimal) + units[position];
        else
            return Math.round(bytes) + units[position];

    } else
        return "0 Bytes";
}