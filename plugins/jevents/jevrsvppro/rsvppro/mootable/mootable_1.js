/**
* @file mootable.js
* @author Mark Fabrizio Jr.
* @date January 24, 2007
* 
* MooTable class takes an existing table as an argument and turns it into a cooler table.
* MooTables allow headings to be resized as well as sorted.
*/

String.extend({
	stripScripts: function(option){
		var scripts = '';
		var text = this.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(){
			scripts += arguments[1] + '\n';
			return '';
		});
		if (option === true) $exec(scripts);
		else if (typeOf(option) == 'function') option(scripts, text);
		return text;
	}
});
var MooTable = new Class({
	
	initialize: function( el, options ){
		this.element = $(el);
		this.options = Object.extend( 
			{ 
				height: '150px',
				resizable: true,
				sortable: true,
				useloading: false,
				position: 'inside',
				section: 100,
				delay: 10,
				fade: true,
				headers: false,
				data: false,
				debug: false,
				flexheight:true
			} , options || {} );
		/* set up our reference arrays */
		this.headers = []; 
		this.rows = [];
		this.fade = this.options.fade ? (window.ie6 ? '' : '<div class="fade"></div>') : '';
		this.loading = true;
		/* initalize variables */
		this.sortasc=true;
		this.sortby=false;
		this.sortby2=false;
		// check that header & at least one row done
		this.headerdone = 0;
		this.rowdone = 0;
		
		if( this.options.debug ){
			this.debug = {};
			debug.log('debug: on');
			this.addEvent( 'buildStart', function(){
				this.debug.startTime = new Date().getTime();	
			});
			this.addEvent( 'buildFinish', function(){
				debug.log( 'build: '+ ( (new Date().getTime() - this.debug.startTime ) / 1000 ) + ' seconds' );
				
			});
			this.addEvent( 'sortStart', function(){
				this.debug.sortStart = new Date().getTime();
			});
			this.addEvent( 'sortFinish', function(){
				debug.log( 'sort: '+ ( (new Date().getTime() - this.debug.sortStart ) / 1000 ) + ' seconds' );
			});
		}
		if( this.options.useloading ){
			this.addEvent( 'loadingStart', function(){
				this.tbody.setStyle('overflow', 'hidden');
				this.tbodyloading.setStyle('display', 'block');
			});
			
			this.addEvent( 'loadingFinish', function(){
				this.tbody.setStyle('overflow', 'auto');
				this.tbodyloading.setStyle('display', 'none');	
			});
		}
		/* create the table */
		this._makeTable(this.element);
		if (!this.div) return;
		if (this.tableWidth) this.div.setStyle('width',this.tableWidth+'px');
		if (this.options.flexheight){
			this.div.setStyle('height', (this.div.getSize().y+ (this.options.resizable ? 8 : 1))+'px');
		}
		else {
			this.div.setStyle('height', this.options.height );
		}
		this._manageHeight();
		this.tbody.addEvent('scroll', function(event){
			this.thead_tr.setStyle( 'left', '-'+this.tbody.scrollLeft+'px' );
			return true;
		}.bind(this));
		this._initDisplayOptions();
	},
	
	_manageHeight: function(){
		var offset = this.options.resizable ? 8 : 1;
		this.tbody.setStyle('height', (this.div.getSize().y - this.thead.getSize().y - offset ) + 'px' );
		if( this.options.useloading ){
			this.tbodyloading.setStyle('height', (this.div.getSize().y - this.thead.getSize().y - offset)  + 'px' );
		}
		this.tbody.setStyle('top', this.thead.getSize().y + 'px' );
		
	},
	_rememberCookies: function(){
		this.headers.each( function( header ){
			var width = this._getWidthCookie( header.element )
			if( width ){
				header.element.setStyle('width', width );
				this._changeColumnWidth( header.element );
			}
		}, this );
	},
	
	_makeTable: function(el){
		this._fireEvent('buildStart');
		if( !el ){
			return;
		}
		this._createTableFramework();
		this.tableWidth = 0;
		if( el.get('tag') == 'table'){
			this.tableWidth = el.getSize().x;
			this._fireEvent('loadingStart');
			this._makeTableFromTable( el );
			return;
		}
		this.div.inject( el, this.options.position );
		this._build();
	},
	
	_makeTableFromTable: function(t,count){
		var rows = typeOf(t) == 'array' ? t : t.getElements('tr');
		if( !$chk(count) ) count = 0;
		var section=0;
		var thead = t.getElement('thead');
		if (thead){
			var tr = thead.getElement('tr');			
			if (tr.getElements('th').length > 0){
				this.headerdone = 1;
				t.setStyle('display', 'none');
				this.div.injectBefore(t);
				if(t.getElement('tfoot')) Element.dispose(t.getElement('tfoot'));
				tr.getElements('th,td').each( function( th ){
					value = th.innerHTML;
					this._addHeader(value);
				}, this);
				this._addBreak();
				this._setHeaderWidth();
			}
		}
		while( count < rows.length && section < this.options.section){
			var tr = rows[count];			
			if (!thead && tr.getElements('th').length > 0){
				this.headerdone = 1;
				t.setStyle('display', 'none');
				this.div.injectBefore(t);
				if(t.getElement('tfoot')) Element.dispose(t.getElement('tfoot'));
				tr.getElements('th,td').each( function( th ){
					value = th.innerHTML;
					this._addHeader(value);
				}, this);
				this._addBreak();
				this._setHeaderWidth();
			}
			else if (tr.getElements('th').length == 0) {
				var values = [];
				tr.getElements('td').each( function( td ){ 
					values.push( td.innerHTML.stripScripts());					
				}, this);
				this.addRow( values );
				if (this.headerdone && !this.rowdone){
					this._setColumnWidths();
					this.rowdone =1;
				}
			}
			count++;
			section++;
		}
		// force the column widths !!
		this._setColumnWidths();
		if( count < rows.length ){
			this.loading = true;
			this._makeTableFromTable.delay(this.options.delay, this, [rows,count] );
		}
		else{
			this.loading = false;
			this._setWidths();
			this._fireEvent('buildFinish');
			this._fireEvent('loadingFinish');
		}
	},
	
	_build: function(){
		if( this.options.headers && typeOf(this.options.headers) == 'array'){
			this.options.headers.each( function( h ){
				switch( typeOf( h ) ){
					case 'string':
						this._addHeader( h.trim()=='' ? '&nbsp;' : h );
						break;
					
					case 'object':
						this._addHeader( h.text || '&nbsp;', h );
						break;
						
					default:
						break;
				}
			},this ); 
		}
		/* do a little cleanup to keep this object reasonable */
		this.options.headers = null;
		if( this.options.data && typeOf( this.options.data ) == 'array' ){
			this._loadData( this.options.data );
		}
	},
	
	loadData: function( data, append ){
		if( !$chk(append) ){ append = true; }
		if( !append ){
			this._emptyData();
		}
		this._loadData( data );
	},
	
	_emptyData: function(){
		this.rows.each( function(row){
			Element.dispose(row.element);
		});
		this.rows = [];
			
	},
	
	_loadData: function( data, index ){
		if( !$chk(index) ) index = 0;
		var section=0;
		if( index == 0 ){
			this._fireEvent( 'loadingStart' );
		}
		for( index = index; index < data.length && section < this.options.section; index++){
			// load data
			var d = data[index];
			switch( typeOf( d ) ){
				case 'array':
				case 'object':
					this.addRow( d );
					break;
				default:
					break;
			}
			section++;
		}
		if( index < data.length ){
			this._setColumnWidths.delay( 20, this );
			this.loading = true;
			this._loadData.delay(this.options.delay, this, [data,index] )
		}
		else{
			this._setColumnWidths();
			this._fireEvent('loadingFinish');
			this._fireEvent('buildFinish');
		}
			
	},
	
	_createTableFramework: function(){
		this.div = new Element('div').addClass('mootable_container');
		this.mootable = new Element('div').addClass( 'mootable' ).inject( this.div );
		this.thead = new Element('div').addClass('thead').inject( this.mootable );
		this.thead_tr = new Element('div').addClass('tr').inject( this.thead );
		this.tbody = new Element('div').addClass('tbody').injectAfter( this.thead );
		this.table = new Element('table').setProperties({cellpadding: '0', cellspacing: '0', border: '0'}).inject(this.tbody);
		this.tablebody = new Element('tbody').inject( this.table );
		if( this.options.useloading ){
			this.tbodyloading = new Element('div').addClass('loading').inject( this.tbody );
			this.tbodyloading.setStyle('opacity', '.84');
		}
		if( this.options.resizable ){
			this.resizehandle = new Element('div').addClass('resizehandle').inject(this.div);
			try {
				var drag = new Drag( this.div, {
					handle: this.resizehandle,
					modifiers: {y: 'height'},
					onComplete: function(){
						this._manageHeight();
					}.bind(this)
				});
				drag.options.modifiers.x=false;
			}
			catch (e){
				var drag = new Drag.Base( this.div, {
					handle: this.resizehandle,
					modifiers: {y: 'height'},
					onComplete: function(){
						this._manageHeight();
					}.bind(this)
				});
				drag.options.modifiers.x=false;
			}
		}
	},

	_addBreak : function (){
		//var cell = new Element('div').inject( this.thead_tr ).addClass('break');
	},

	
	_addHeader: function( value, opts ){
		var options = Object.extend({
			fixedWidth: false,
			defaultWidth: '100px',
			sortable: true,
			key: null,
			fade: true
		}, opts || {} ); 
		var cell = new Element('div').inject( this.thead_tr ).addClass('th');
		new Element('div', {html: value}).addClass('cell').inject( cell );
		var h = {
			element: cell,
			value: value,
			options: options
		};
		h.element.col = this.headers.length;
		this.headers.push( h );
		var width = this._getWidthCookie( h.element );
		if( width && !h.options.fixedWidth ){
			h.element.setStyle('width', width );
			//this._changeColumnWidth( h.element );
		}else{
			h.element.setStyle('width', h.options.defaultWidth );
		}
		
		h.width = h.element.getStyle('width');
		if( this.options.sortable && h.options.sortable ){
			h.element.addClass('sortable');
			h.element.addEvent('mouseup', function(ev){
				this.sort( h.element.col );
			}.pass(h.element, this));
		}
		
		if( !h.options.fixedWidth ){
			var handle = new Element('div', {html: '&nbsp;'}).addClass('resize').inject( h.element );
			try {
				var resizer = new Drag(h.element, {
					handle: handle,
					modifiers:{x: 'width'},
					onComplete: function(){
						if( h.element.getSize().x < 10 ) {
							h.element.setStyle('width', '10px');
							this._setHeaderWidth();
						}
						this._setWidthCookie( h.element );
						this._setColumnWidths();
						this.thead.removeClass('dragging');
						h.element.removeClass('dragging');
					}.bind(this),

					onStart: function(ele){
						if( this.options.sortable) this.dragging = true;
						this.thead.addClass('dragging');
						ele.addClass('dragging');
					}.bind(this),

					onDrag: function(ele){
						this._setHeaderWidth();
					}.bind(this)
				} );
				resizer.options.modifiers.y=false;
			}
			catch (e){
				var resizer = new Drag.Base(h.element, {
					handle: handle,
					modifiers:{x: 'width'},
					onComplete: function(){
						if( h.element.getSize().x < 10 ) {
							h.element.setStyle('width', '10px');
							this._setHeaderWidth();
						}
						this._setWidthCookie( h.element );
						this._setColumnWidths();
						this.thead.removeClass('dragging');
						h.element.removeClass('dragging');
					}.bind(this),

					onStart: function(ele){
						if( this.options.sortable) this.dragging = true;
						this.thead.addClass('dragging');
						ele.addClass('dragging');
					}.bind(this),

					onDrag: function(ele){
						this._setHeaderWidth();
					}.bind(this)
				} );
				resizer.options.modifiers.y=false;
			}
			// best fit
			handle.addEvent('dblclick', this.bestFit.pass( h.element.col,this) ); 
			
		}
		h.element.addEvent('mouseover', function(){
			this.addClass('mouseover');
		});
		h.element.addEvent('mouseout', function(){
			this.removeClass('mouseover');
		});
	},
	
	_createRow: function( data ){
		var row = {};
		row.element = new Element( 'tr' );
		row.cols = [];
		i=0;
		this._fireEvent( 'beforeRow', data );
		switch( typeOf( data ) ){
			case 'array':
				for(var i=0; i<this.headers.length; i++ ){
					var cell = this._createCell( data[i] );
					cell.element.addClass('c'+i).inject(row.element);
					row.cols.push(cell);
				}
				break;
			case 'object':
				row.data = data;
				for(var i=0; i<this.headers.length; i++ ){
					header = this.headers[i];
					var text = header.options.key ? data[header.options.key] : '&nbsp;';
					var cell = this._createCell( text, header.options.fade );
					cell.element.addClass('c'+i).inject(row.element);
					row.cols.push(cell);
				}
				break;
				
			default:
				// bad object
				break;
		}
		this._fireEvent( 'afterRow', [data, row] );
		return row;	
	},
	
	addRow: function( data ){
		var row = this._createRow( data );
		row.element.inject(this.tablebody);
		row.element.addClass( this.rows.length % 2 == 0 ? 'even' : 'odd' );
		this.rows.push( row );
	},
	
	_createCell: function( value, fade ){
		if( !$chk(fade) ){ fade = true; }
		var cell = {};
		cell.value = value;
		cell.element = new Element('td',  {html:'<div class="cell">'+( fade ? this.fade : '' )+'<span>'+value+'</span>&nbsp;</div>' }); 
		return cell;
	},
	
	_setColumnWidths: function(){
		this._setWidths();
		if( this.rows.length > 0 ){
			for(i=0;i<this.headers.length;i++){
				var w = this.headers[i].element.getStyle('width');
				w = window.ie ? (w.replace(/px/,"") - 2)+'px' :  w;
				this.rows[0].cols[i].element.setStyle('width', w);
				var rows = $$('td.c'+i).each(function(row, index){
					row.getElement('div').setStyle('width', w);
				});
			}
		}
		this._setWidths();
	},
	
	_setHeaderWidth: function(){
		var width=0;
		this.headers.each(function(h){
			width += h.element.getSize().x;
		});
		this.thead_tr.setStyle('width', width+'px');
		this.tablewidth = width;
	},
	
	_setWidths: function(){
		this._setHeaderWidth();
		var width = this.thead_tr.getSize().x;
		this.table.setStyle( 'width', this.thead_tr.getStyle('width'));
		this.table.setProperty( 'width', this.thead_tr.getStyle('width'));
		this.tbody.fireEvent('scroll');
	},
	
	_copyProperties: function(from,to){
		//to.setProperty( 'class', from.getProperty('class') || '' );
		//to.setProperty( 'style', from.getProperty('style') || '' );
	},
	_initDisplayOptions: function(){
		this.displayOptions = new Element('div').addClass('mootable_options');
		this.form = new Element('form').inject( this.displayOptions );
		var i=0;
		this.headers.each( function( header ){
			var id = 'mootable_h'+i;
			var checkbox = new Element('input').setProperty('type','checkbox').setProperty('id',id).setProperty('name',id).inject(this.form);
			checkbox.setProperty('checked', 'true');
			checkbox.addEvent('click', this.toggleColumn.pass(i,this) );
			var label = new Element('label', {html:header.value}).setProperty('for',id).setProperty('htmlFor',id).inject(this.form);
			i++;
			if( i < this.headers.length ){
				new Element('br').injectAfter(label);
			}
		}, this);
		this.displayOptionsTrigger = new Element('div').addClass('displayTrigger').inject( this.thead );
		this.displayOptionsTrigger.addEvent('click', this._toggleDisplayOptions.bind(this) );
		this.displayOptions.addClass('displayOptions').injectAfter( this.displayOptionsTrigger );
	},
	toggleColumn: function( col ){
		var checked = this.form['mootable_h'+col].checked;
		this.rows.each( function(row){
			row.cols[col].element.setStyle('display', checked ? '' : 'none');	
		});
		this.headers[col].element.setStyle('display', checked ? '' : 'none');
		this._setHeaderWidth();
		this._setWidths();
	},
	_toggleDisplayOptions: function(ev){
		if( this.displayOptions.getStyle('display') == 'none' ){
			this.displayOptions.setStyle('display', 'block');
			document.addEvent('mousemove', this._monitorDisplayOptions.bind(this) );
		}
		else{
			this.displayOptions.setStyle('display', 'none');
			document.removeEvent( 'mousemove', this._monitorDisplayOptions );
		}
	},
	_monitorDisplayOptions: function(ev){
		var e = new Event( ev );
		var pos = this.displayOptions.getPosition();
		if( e.page.x < pos.left || e.page.x > (pos.left + pos.width) ){
			this.displayOptions.setStyle('display', 'none');
			document.removeEvent( 'mousemove', this._monitorDisplayOptions );
		}
		else if( e.page.y < pos.top || e.page.y > (pos.top + pos.height) ){
			this.displayOptions.setStyle('display', 'none');
			document.removeEvent( 'mousemove', this._monitorDisplayOptions );
		}
	},
	_zebra: function(){
		var c = 0;
		this.rows.each( function(row) {
				row.element.addClass( c%2 == 0 ? 'odd' : 'even' );
				row.element.removeClass( c%2 == 1 ? 'odd' : 'even' );
				c++;
		});
	},
	_setWidthCookie: function( ele ){
		//Cookie.set('mootable_h_'+this.headers[ele.col].value , ele.getStyle('width') );
		var cookie = new Cookie('mootable_h_'+this.headers[ele.col].value , null).write(ele.getStyle('width'));
	},
	_getWidthCookie: function( ele ){
		var cookie =- new Cookie('mootable_h_'+this.headers[ele.col].value).read();
		return cookie;
		//return Cookie.get('mootable_h_'+this.headers[ele.col].value);
	},
	sort: function( col ){
		this._fireEvent('sortStart');
		if( this.rows.length == 0 ){
			return;
		}
		this.rows[0].cols.each( function( col ){
			col.element.setProperty('width', '');
			col.element.setStyle('width', 'auto' );
		} );
		if( this.dragging ){
			this.dragging = false;
			return;
		}
		if( $chk(this.sortby) ){
			this.headers[this.sortby].element.removeClass( 'sorted_'+ (this.sortasc ? 'asc' : 'desc' ) );
		}
		if( $chk(this.sortby) && this.sortby == col ){
			this.sortasc = !this.sortasc;
		}
		else if( $chk(this.sortby) ){
			this.sortby2 = this.sortby;
			this.sortasc = true;
		}
		this.sortby = col;
		this.headers[this.sortby].element.addClass( 'sorted_'+ (this.sortasc ? 'asc' : 'desc' ) );
		this.rows.sort( this.rowCompare.bind(this) );
		this.rows.each( function( item ){
			Element.dispose(item.element);
		});
		i=0;
		this.rows.each( function( item ){
			item.element.addClass( i%2 == 0 ? 'even' : 'odd' );
			item.element.removeClass( i%2 == 0 ? 'odd' : 'even' );
			item.element.inject(this.tablebody);
			i++;
		}, this );
		this._setColumnWidths();
		this._setWidths();
		this._fireEvent('sortFinish');
	},
	rowCompare: function( r1, r2 ){
		a = r1.cols[this.sortby].value;
		b = r2.cols[this.sortby].value;
		if( a > b ){
			return this.sortasc ? 1 : -1;
		}
		if( a < b ){
			return this.sortasc ? -1 : 1;
		}
		if( this.sortby2 ){
			a = r1.cols[this.sortby2].value;
			b = r2.cols[this.sortby2].value;
			if( a > b ){
				return this.sortasc ? 1 : -1;
			}
			if( a < b ){
				return this.sortasc ? -1 : 1;
			}
		}
		return 0;
	},
	bestFit: function(col){
		var max = 0;
		this.table.getElements('td.c'+col+' span').each( function( el ){
			s = el.getSize().x;
			if( s > max ) max = s;
		});                          
		this.headers[col].element.setStyle('width', (max+(this.headers[col].options.fade && this.options.fade ? 5 : 0)) + 'px' );
		this._setWidthCookie( this.headers[col].element );
		this._setHeaderWidth();
		this._setColumnWidths( this.headers[col] );
	},
	
	addEvent: function(type, fn){
		this.events = this.events || {};
		this.events[type] = this.events[type] || {'keys': []};
		if (!this.events[type].keys.test(fn)){
			this.events[type].keys.push(fn);
		}
		return this;
	},
	
	_fireEvent: function(type,args){ 
		if (this.events && this.events[type]){
			this.events[type].keys.each(function(fn){
				fn.bind(this, args)();
			}, this);
		}
	}	
});

