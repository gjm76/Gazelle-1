
function ShowDead(x) {
   location.href='tools.php?type=&action=shows&dead=1';
   x.value = 'Switching...';
   x.disabled = 'true';
}

function ShowAll(x) {
	location.href='tools.php?type=&action=shows&dead=0';
	x.value = 'Switching...';
	x.disabled = 'true';
}	

function Clear(x) {
	x.value = 'Clearing...';
	//x.disabled = 'true';
}
	
   