var i=1;
var max=10;

function addq(){
	var q=$("#addq");
	if(i > max) return;
 	var ele='<tr><td>选项: </td><td><input type="text" name="q'+ i +'" value=""></td></tr>';
 	i++;
	q.append(ele);
}