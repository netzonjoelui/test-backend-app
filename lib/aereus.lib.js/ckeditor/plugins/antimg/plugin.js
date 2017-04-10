/**
 * This plugin handles using AntFs to add photos to the editor
 */
CKEDITOR.plugins.add('antimg',
{
	init: function( editor )
	{
		editor.addCommand('insertAntImg',
			{
				exec : function( editor )
				{    
					var cbrowser = new AntFsOpen();
					cbrowser.filterType = "jpg:jpeg:png:gif";
					cbrowser.cbData.editor = editor;
					cbrowser.onSelect = function(fid, name, path) 
					{
						this.cbData.editor.insertHtml("<img src=\"http://" + document.domain + "/files/images/"+fid+"\" />");
					}
					cbrowser.showDialog();
				}
			}
		);

		editor.ui.addButton('AntImg',
			{
				label: 'Insert Image',
				command: 'insertAntImg',
				icon: this.path + 'images/antimg.png'
			}
		);
	}
} );
