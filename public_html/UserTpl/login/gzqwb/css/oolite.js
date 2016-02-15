function has(arr,value) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i] === value) {
            return true
        }
    }
    return false
};
function outerHTML(o) {
    if (o) {
        var _p = new E('p');
        _p.appendChild(o);
        return _p.innerHTML
    } else {
        return ''
    }
}
function Xin(elm) {
    if (typeof(elm) == "string") {
        elm = document.getElementById(elm)
    }
    if (!elm) {
        return
    }
    elm.E = function(str) {
        var a = str.split('.');
        var c = [];
        var list = this.getElementsByTagName(a[0]);
        if (str.indexOf('.') == -1) {
            return list
        } else {
            for (var i = 0; i < list.length; i++) {
                if (str.indexOf('=') > 0) {
                    var b = a[1].split('=');
                    if (list[i][b[0]] == b[1]) {
                        c.push(Xin(list[i]))
                    }
                } else {
                    if (list[i].className == a[1]) {
                        c.push(Xin(list[i]))
                    }
                }
            }
        }
        return c
    };
    elm.css = function(st) {
        var elmStyle = this.style;
        for (var itm in st) {
            switch (itm) {
            case 'float':
                try{this.style['cssFloat'] = this.style['styleFloat'] = st[itm];}
				catch(err){}
                break;
            case 'opacity':
                try{this.style.opacity = st[itm];
                this.style.filter = "alpha(opacity=" + Math.round(st[itm] * 100) + ")";}
				catch(err){}
                break;
            case 'className':
                try{this.className = st[itm];}
				catch(err){}
                break;
            default:
                try{this.style[itm] = st[itm];}
				catch(err){}
                break
            }
        }
        return this
    };
    return elm
}
function next(o) {
    var o_ = o.nextSibling;
    if (!o_) return null;
    while (o_.nodeType != 1) {
        o_ = o_.nextSibling;
        if (!o_) {
            return null
        }
    };
    return Xin(o_);
}
function last(o) {
    var o_ = o.previousSibling;
    if (!o_) return null;
    while (o_.nodeType != 1) {
        o_ = o_.previousSibling;
        if (!o_) {
            return null
        }
    };
    return Xin(o_);
}
function XXin(S) {
    var list = [];
    list = Xin(document.body).E(S);
    return list
}
function E(tag, opt) {
    if (!tag) return;
    var _new = document.createElement(tag);
    if (!opt) {
        return Xin(_new)
    } else {
        for (itm in opt) {
            if (itm == 'css') {
                Xin(_new).css(opt.css)
            } else {
                _new[itm] = opt[itm]
            }
        }
    }
    return Xin(_new);
}
function mdrag(o, h) {
    var flag = {
        a: 0,
        x: 0,
        y: 0,
        z: 0
    };
    if (!h) {
        h = Xin(o)
    }
    o = Xin(o);
    if (!o) {
        return false
    }
    h.style.cursor = 'move';
    h.onmousedown=h.onmouseup= function(e) {
        e = e ? e: window.event;
        if (flag.a == 0) {
            flag = {
                a: 1,
                x: e.clientX - o.offsetLeft,
                y: e.clientY - o.offsetTop,
                z: o.style.zIndex
            };
            o.css({
                position: 'absolute',
                left: o.offsetLeft + 'px',
                top: o.offsetTop + 'px'
            })
        } else {
            o.css({
                left: e.clientX - flag.x + 'px',
                top: e.clientY - flag.y + 'px',
                zIndex: flag.z
            });
            flag = {
                a: 0,
                x: 0,
                y: 0,
                z: 0
            }
        }
    };
    addEvent(document, 'mousemove', 
    function(e) {
        if (flag.a == 1) {
            e = e ? e: window.event;
            o.css({

                left: e.clientX - flag.x + 'px',
                top: e.clientY - flag.y + 'px',
                zIndex: 999
            })
        }
    })
}
function addCss() {
    var doc,
    cssCode;
    if (arguments.length == 1) {
        doc = document;
        cssCode = arguments[0]
    } else if (arguments.length == 2) {
        doc = arguments[0];
        cssCode = arguments[1]
    } else {
        return
    }
    cssCode = cssCode + "\n";
    var headElement = doc.getElementsByTagName("head")[0];
    var styleElements = headElement.getElementsByTagName("style");
    if (styleElements.length == 0) {
        if (doc.createStyleSheet) {
            doc.createStyleSheet()
        } else {
            var tempStyleElement = doc.createElement('style');
            tempStyleElement.setAttribute("type", "text/css");
            headElement.appendChild(tempStyleElement)
        }
    }
    var styleElement = styleElements[0];
    var media = styleElement.getAttribute("media");
    if (media != null && !/screen/.test(media.toLowerCase())) {
        styleElement.setAttribute("media", "screen")
    }
    if (styleElement.styleSheet) {
        styleElement.styleSheet.cssText += cssCode
    } else if (doc.getBoxObjectFor) {
        styleElement.innerHTML += cssCode
    } else {
        styleElement.appendChild(doc.createTextNode(cssCode))
    }
}
function Ajax(o) {
    var m,u,d,e,x,h;
    if (!o.u) {
        return false
    }
    m = (o.m ? o.m.toUpperCase() : 'POST');
    e = (o.e ? o.e: function() {});
    d = (o.d ? o.d: '');
    u = o.u + (o.u.indexOf('?') > 0 ? '&': '?') + Math.random();
    var x = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
    x.onreadystatechange = function() {
        if (x.readyState == 4 && x.status == 200) {
            e(x.responseText);
        }
    };
    x.open(m, u, true);
    if (m == 'POST') {
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
    }
    x.send(d)
}

function form2json(formid) {
    var f = Xin(formid),l=f.length,o,r={};
	for(var i=0;i<l;i++){
		o=f.elements[i];
		if(o.type=='radio'){
			if(o.checked)r[o.name]=o.value;
		}else if(o.type=='checkbox'){
			if(o.checked){
				if(r[o.name]){r[o.name].push(o.value);}
				else{r[o.name]=[o.value];}
			}
		}
		else if(o.type!='button'&&o.type!='submit'){r[o.name]=o.value;}
	}
	return r;
}

function addEvent(element, type, handler) {
    if (!handler.$$guid) handler.$$guid = addEvent.guid++;
    if (!element.events) element.events = {};
    var handlers = element.events[type];
    if (!handlers) {
        handlers = element.events[type] = {};
        if (element["on" + type]) {
            handlers[0] = element["on" + type]
        }
    }
    handlers[handler.$$guid] = handler;
    element["on" + type] = handleEvent
};
addEvent.guid = 1;
function removeEvent(element, type, handler) {
    if (element.events && element.events[type]) {
        delete element.events[type][handler.$$guid]
    }
};
function handleEvent(event) {
    event = event || window.event;
    var handlers = this.events[event.type];
    for (var i in handlers) {
        this.$$handleEvent = handlers[i];
        this.$$handleEvent(event)
    }
};

function pbox(o,e){
	e=e||window.event;
	var b,t,c,x,f,s;
	s=e.target||e.srcElement;
	if(!o){o={w:400,h:300,t:'无标题',c:'无内容'};}
    b = new E('div', {className: 'pbox'});
    t = new E('div', {className: 'pbox_title',innerHTML:o.t});
    c = new E('div', {className: 'pbox_content',innerHTML:o.c});
    x = new E('div', {className: 'pbox_close',innerHTML: 'x'
	});
	f=o.f?o.f:s.parentNode.parentNode;
	f=f?f:document.body;
    b.appendChild(x);b.appendChild(t);b.appendChild(c);
	f.appendChild(b);
   // mdrag(b, t);
	
	var p={x:0,y:0};
	if(e.pageX){
		p.x=e.pageX;
		p.y=e.pageY;
	}else{
		try{
			var sT=document.documentElement.scrollTop||document.body.scrollTop;
			var sL=document.documentElement.scrollLeft||document.body.scrollLeft;
			p.x=e.clientX+sL-document.body.clientLeft;
			p.y=e.clientY+sT-document.body.clientTop;
		}catch(err){}
	}
    b.css({
        position: 'absolute',
        zIndex: 99,
        width: o.w + 'px',
        height: o.h+'px',
        backgroundColor: '#FFF',
        border: '1px solid #666',
        left: p.x + 'px',
		top:p.y+'px'
    });
    t.css({
        padding: '3px',fontSize:'12px',
		margin:'1px'
    });
    c.css({
        padding: '3px',margin:'1px',
        height: 'auto',
        maxHeight: o.h + 'px',
        border: '1px solid #666',
        overflow: 'hidden',
        overflowY: 'auto',fontSize:'12px'
    });
    b.style.height = c.offsetHeight+t.offsetHeight+3 + 'px';
    b.style.top = p.y + 'px';
    x.css({
        float: 'right',
        cursor: 'pointer',
        color: '#F00',fontFamily:'Wingdings',fontWeight:'bolder',
        padding: '2px 3px',fontSize:'12px'
    });
	var keyclose=function(e){
		e=e||window.event;
		var k=e.which||e.keyCode;
		if(k==27)b.close();
	};
    b.close = function() {
        b.parentNode.removeChild(b);
		removeEvent(document.body,'keyup',keyclose);
    };
    x.onclick = function() {
        b.close();
    };
	addEvent(document.body,'keyup',keyclose);
    b.x = x;
    b.c = c;
    b.t = t;
	mdrag(b,t);
    return b
}

function save(){
	document.execCommand("SaveAs",0,0);
	return;
}
function preview() { 
	window.clipboardData.setData("Text",Xin('data').outerHTML);
	try{
	var ExApp = new ActiveXObject("Excel.Application");
	var ExWBk = ExApp.workbooks.add();
	var ExWSh = ExWBk.worksheets(1)
	ExApp.DisplayAlerts = false;
	ExApp.visible = true;
	}catch(e){
		alert("您的电脑没有安装Microsoft Excel软件！");
		return false;
	} 
	ExWBk.worksheets(1).Paste;	
}
