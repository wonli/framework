var o

function BrowseServer(obj)
{
	o = obj
	// You can use the "CKFinder" class to render CKFinder in a page:
	var finder = new CKFinder();
	finder.basePath = '/ckfinder/';	// The path for the installation of CKFinder (default = "/ckfinder/").
	finder.selectActionFunction = SetFileField;
	finder.popup();
}

	// This is a sample function which is called when a file is selected in CKFinder.
function SetFileField(fileUrl)
{

     $(o).prev('input').val( fileUrl );
}