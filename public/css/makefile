all: connexions.css userItems.css 

connexions.css: main.sass		\
		colors.sass		\
		content.sass		\
		grids.sass		\
		libraries.sass		\
		pagination.sass		\
		itemScope.sass		\
		sprites.sass		\
		tabs.sass		\
		tagCloud.sass		\
		template.sass		\
		ui-form.sass		\
		themes/default/jquery-ui.css
	sass -t compact main.sass connexions.css

userItems.css: userItems.sass colors.sass
	sass -t compact userItems.sass userItems.css

