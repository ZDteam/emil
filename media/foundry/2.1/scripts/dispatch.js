!function(window,undefined){if(window.dispatch===undefined){var uid=function(p,s){return(p?p:"")+Math.random().toString().replace(".","")+(s?s:"")},dispatch=window.dispatch=function(manifest,exports){var setter=!1;if("object"==typeof manifest){if(typeof manifest.name===undefined)return;setter=!0}"string"==typeof manifest&&(manifest={name:manifest});var parcel=dispatch.parcels[manifest.name];return parcel===undefined?(parcel=dispatch.parcels[manifest.name]=new Parcel(manifest.name),parcel.add(manifest,exports)):setter&&parcel.add(manifest,exports),parcel.get()
};dispatch.parcels={},dispatch.dropsite={},dispatch.delivering=!1,dispatch.queue=[],dispatch.deliver=function(parcel,dropsite,forceDeliver){if(dispatch.delivering&&!forceDeliver)return dispatch.queue.push(arguments),void 0;if(dispatch.delivering=!0,dropsite.addParcel(parcel),parcel.exports!==undefined){var i;for(i=0;i<dropsite.sites.length;i++){var site=dropsite.sites[i];parcel.sentTo[site.id]||site.target&&(site.target.apply(window,[parcel.exports,parcel.manifest]),parcel.sentTo[site.id]=!0)}if(dispatch.queue.length>0){var args=dispatch.queue.shift();
dispatch.deliver.apply(this,[args[0],args[1],!0])}else dispatch.delivering=!1}},dispatch.to=function(name){return name!==undefined?dispatch.dropsite[name]||(dispatch.dropsite[name]=new Dropsite(name)):void 0};var Dropsite=function(name){this.name=name,this.sites=[],this.parcels=[]};Dropsite.prototype.at=function(site){var dropsite=this;if("function"==typeof site){var siteId=uid(this.name+"/");this.sites.push({id:siteId,target:site});var i;for(i=0;i<this.parcels.length;i++){var parcel=this.parcels[i];dispatch.deliver(parcel,dropsite)
}}return this},Dropsite.prototype.addParcel=function(newParcel){var i,parcelExists=!1;for(i=0;i<this.parcels.length;i++){var parcel=this.parcels[i];if(parcel==newParcel){parcelExists=!0;break}}parcelExists||this.parcels.push(newParcel)};var Parcel=function(manifest){this.name=manifest.name,this.dropsites=[],this.parcels=[]};Parcel.prototype.add=function(manifest,exports){return this.parcels.push({manifest:manifest,exports:exports,sentTo:{}})},Parcel.prototype.get=function(){return this.indexLocked=!1,this.index=this.parcels.length-1,this
},Parcel.prototype.intendedFor=function(name){var parcel;for(i in this.parcels)if(parcel=this.parcels[i],parcel.manifest.recipient==name)return this.index=i,this.indexLocked=!0,this},Parcel.prototype.containing=function(exports){if(this.indexLocked)return this;var parcel=this.parcels[this.index];return parcel.exports!==undefined?this.index=this.add({name:this.name},exports)-1:parcel.exports=exports,this},Parcel.prototype.to=function(dropsite,forceDeliver){if(dropsite===undefined)return this;if(parcel=this.parcels[this.index],"string"==typeof dropsite){var name=dropsite;
parcel.manifest.recipient!==undefined&&(parcel.manifest.recipient=name),dropsite=dispatch.to(name)}return"function"==typeof dropsite&&(dropsite=dispatch.to("Custom dropsite for "+parcel.manifest.name).at(dropsite)),this.dropsites.push(dropsite),this.dropsiteLocked?forceDeliver&&dispatch.deliver(parcel,dropsite):this.toAll(),this},Parcel.prototype.onlyTo=function(dropsite){return this.dropsiteLocked=!0,this.to(dropsite,!0),this},Parcel.prototype.toAll=function(){this.dropsiteLocked=!1;var i,parcel=this.parcels[this.index],dropsites=this.dropsites;
for(i=0;i<dropsites.length;i++){var dropsite=dropsites[i];dispatch.deliver(parcel,dropsite)}return this}}}(window);