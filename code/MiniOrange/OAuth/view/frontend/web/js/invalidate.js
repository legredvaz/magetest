function mo_invalidate($, fullname, fistname) {
	$('.greet.welcome').html('<span data-bind="text: new String(\'Welcome, %1!\').replace(\'%1\', customer().firstname)">Welcome, '+fistname+'!</span>');
	$('.customer-name span').html(fullname);
}
