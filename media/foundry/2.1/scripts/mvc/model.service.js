!function(){var moduleFactory=function($){var module=this;$.require().script("mvc/model").done(function(){var exports=function(){var convert=function(method,func){return"function"==typeof method?function(){var ret,old=this._service;return this._service=func,ret=method.apply(this,arguments),this._service=old,ret}:method};$.Model.service=function(properties){var func=function(newProps){return $.Model.service($.extend({},properties,newProps))};for(var name in properties)func[name]=convert(properties[name],func);
return func}};exports(),module.resolveWith(exports)})};dispatch("mvc/model.service").containing(moduleFactory).to("Foundry/2.1 Modules")}();