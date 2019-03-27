![alt tag](https://raw.githubusercontent.com/hurkanaras/Hurkan-Switch-Plugin/master/demo.png)

hurkanSwitch on/off jQuery Plugin
===
Contact
---
Hürkan ARAS
hurkanaras@gmail.com

#Example 1
---

<div id="demo">
	<input name="demo" />
</div>
 ```javascript  
 var options = {
	'onTitle':'ON',
	'offTitle':'OFF',
	'onColor':'success', // danger,success,info,primary,default,warning
	'offColor':'danger', // danger,success,info,primary,default,warning
	'animate':true, //or false
	'responsive':false,  //or true 
	'checked':'your value', // default input checked value="your value" OR checked:'.input-selector'
	'selected':function(input,status){ // input selector event 
	 // $(input)
	 // status ==> on / off - String
		console.log(status);
	},
 	 'offConfirm':function(input){ 
		
			if(confirm("Are you sure?")){
				$(input).trigger("click",true);
			}
		
			return false;
		},
	 'onConfirm':function(input){
		
		if(confirm("Are you sure?")){
			$(input).trigger("click",true);
		}
		
		return false;
	},
	
};

$('#demo').hurkanSwitch(options);
 ```
#Example 2
---
```html
<div id="demo2">
	<input data-title="On" data-on-color="success"  value="your value 1"  data-on="true" name="demo3" type="radio" />
	<input data-title="Off" data-off-color="success" value="your value 2" data-off="true" name="demo3" type="radio" />
</div>
```
```javascript
$('#demo2').hurkanSwitch({
	'checked':'your value 2'
});
```

