/**
 * jsDump
 * Copyright (c) 2008 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 * Date: 5/15/2008
 * @projectDescription Advanced and extensible data dumping for Javascript.
 * @version 1.0.0
 * @author Ariel Flesler
 * @link {http://flesler.blogspot.com/2008/05/jsdump-pretty-dump-of-any-javascript.html}
 */
var jsDump;

(function(){
	function quote( str, isKey ){
        var html    = str.toString().replace(/"/g, '\\"')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;');

		return '<span class="'+ (isKey === true ? 'key' : 'string') +'">'
               + '"'    //(isKey === true ? '' : '"')
               + html
               + '"'    //(isKey === true ? '' : '"')
               +'</span>';
        /*
		return '<span class="string">"'
                + str.toString().replace(/"/g, '\\"') + '"</span>';
        // */
	}
	function literal( o ){
		return '<span class="literal">'+ o + '</span>';
	}
	function join( pre, arr, post ){
		var s = jsDump.separator(),
			base = jsDump.indent(),
			inner = jsDump.indent(1);
		if( arr.join )
			arr = arr.join( ',' + s + inner );
		if( !arr )
			return pre + post;
		return [ pre, inner + arr, base + post ].join(s);
	}
	function array( arr ){
		var i = arr.length, ret = Array(i);
		this.up();
		while( i-- )
			ret[i] = this.parse( arr[i] );
		this.down();
		return join( '<span class="array">[</span>',
                     ret,
                     '<span class="array">]</span>' );
	}

	var reName = /^function (\w+)/;

    // Populate the class2type map
    var class2type  = {
        '[object Boolean]': 'boolean',
        '[object Number]':  'number',
        '[object String]':  'string',
        '[object Function]':'function',
        '[object Array]':   'array',
        '[object Date]':    'date',
        '[object RegExp]':  'regexp',
        '[object Object]':  'object'
    };

	jsDump = {
		parse:function( obj, type ){//type is used mostly internally, you can fix a (custom)type in advance
			var parser = this.parsers[ type || this.typeOf(obj) ];
			var ptype  = typeof parser;

			return (ptype == 'function'
                        ? parser.call( this, obj )
                        : (ptype == 'string'
                            ? parser
                            : this.parsers.error
                        )
            );
		},
		typeOf:function( obj ){
            var type    = (obj == null
                            ? String(obj)
                            : class2type[ toString.call(obj) ] || 'object');

            if (false)  //type === 'object')
            {
                // some browsers (FF) consider regexps functions
				if      (obj.exec)                      type = 'regexp';
                else if (obj.scrollBy)                  type = 'window';
                else if (obj.nodeName == '#document')   type = 'document';
                else if (obj.nodeName)                  type = 'node';
                else if (obj.item)                      type = 'nodelist';
                else if (obj.callee)                    type = 'arguments';
                //IE reports functions like alert, as objects
                else if (obj.call ||
                        (obj.constructor != Array) &&
                        //an array would also fall on this hack
					    (obj+'').indexOf(f) != -1)      type = 'function';
                else if ('length' in obj)               type = 'array';
            }
            return type;


			var type = typeof obj,
				f = 'function';//we'll use it 3 times, save it
			return type != 'object' && type != f ? type :
				!obj ? 'null' :
				obj.exec ? 'regexp' :// some browsers (FF) consider regexps functions
				obj.getHours ? 'date' :
				obj.scrollBy ? 'window' :
				obj.nodeName == '#document' ? 'document' :
				obj.nodeName ? 'node' :
				obj.item ? 'nodelist' : // Safari reports nodelists as functions
				obj.callee ? 'arguments' :
				obj.call || obj.constructor != Array && //an array would also fall on this hack
					(obj+'').indexOf(f) != -1 ? f : //IE reports functions like alert, as objects
				'length' in obj ? 'array' :
				type;
		},
		separator:function(){
			return this.multiline ? this.HTML ? '<br />' : '\n' : this.HTML ? '&nbsp;' : ' ';
		},
		indent:function( extra ){// extra can be a number, shortcut for increasing-calling-decreasing
			if( !this.multiline )
				return '';
			var chr = this.indentChar;
			if( this.HTML )
				chr = chr.replace(/\t/g,'   ').replace(/ /g,'&nbsp;');
			return Array( this._depth_ + (extra||0) ).join(chr);
		},
		up:function( a ){
			this._depth_ += a || 1;
		},
		down:function( a ){
			this._depth_ -= a || 1;
		},
		setParser:function( name, parser ){
			this.parsers[name] = parser;
		},
		// The next 3 are exposed so you can use them
		quote:quote, 
		literal:literal,
		join:join,
		_depth_: 1,
		// This is the list of parsers, to modify them, use jsDump.setParser
		parsers:{
			window: '[Window]',
			document: '[Document]',
			error:'[ERROR]', //when no parser is found, shouldn't happen
			unknown: '[Unknown]',
			'null':'null',
			undefined:'undefined',
			'function':function( fn ){
				var ret = 'function',
					name = 'name' in fn ? fn.name : (reName.exec(fn)||[])[1];//functions never have name in IE
				if( name )
					ret += ' ' + name;
				ret += '(';
				ret = [ ret, this.parse( fn, 'functionArgs' ), '){'].join('');
				return join( ret, this.parse(fn,'functionCode'), '}' );
			},
			array: array,
			nodelist: array,
			arguments: array,
			object:function( map ){
				var ret = [ ];
				this.up();
				for( var key in map ) {
                  if (/__$/.test(key)) { continue; }
					ret.push( this.parse(key,'key') + ': ' + this.parse(map[key]) );
                }
				this.down();
				return join( '{', ret, '}' );
			},
			node:function( node ){
				var open = this.HTML ? '&lt;' : '<',
					close = this.HTML ? '&gt;' : '>';
				var tag = node.nodeName.toLowerCase(),
					ret = open + tag;
				for( var a in this.DOMAttrs ){
                  if (/__$/.test(a)) { continue; }
					var val = node[this.DOMAttrs[a]];
					if( val )
						ret += ' ' + a + '=' + this.parse( val, 'attribute' );
				}
				return ret + close + open + '/' + tag + close;
			},
			functionArgs:function( fn ){//function calls it internally, it's the arguments part of the function
				var l = fn.length;
				if( !l ) return '';
				var args = Array(l);
				while( l-- )
					args[l] = String.fromCharCode(97+l);//97 is 'a'
				return ' ' + args.join(', ') + ' ';
			},
			key:function(str) {
                //object calls it internally, the key part of an item in a map
	            return quote( str, true );
            },
			functionCode:'[code]', //function calls it internally, it's the content of the function
			attribute:quote, //node calls it internally, it's an html attribute value
			string:quote,
			date:quote,
			regexp:literal, //regex
			number:literal,
			'boolean':literal
		},
		DOMAttrs:{//attributes to dump from nodes, name=>realName
			id:'id',
			name:'name',
			'class':'className'
		},
		HTML:false,//if true, entities are escaped ( <, >, \t, space and \n )
		indentChar:'  ',//indentation unit
		multiline:true //if true, items in a collection, are separated by a \n, else just a space.
	};

})();

if (typeof exports !== 'undefined') {
	exports.jsDump = jsDump;
}
