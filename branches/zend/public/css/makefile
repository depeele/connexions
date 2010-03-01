all: connexions.css userItems.css users.css urlItems.css

connexions.css: main.sass		\
    		mixins.sass		\
		colors.sass		\
		sizes.sass		\
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

userItems.css: mixins.sass userItems.sass colors.sass sizes.sass
	sass -t compact userItems.sass userItems.css

users.css: mixins.sass users.sass colors.sass sizes.sass
	sass -t compact users.sass users.css

urlItems.css: mixins.sass urlItems.sass colors.sass sizes.sass urls.sass
	sass -t compact urlItems.sass urlItems.css

sprites.sass:	urls.sass

#connexions.css: main.less		\
#		colors.less		\
#		sizes.less		\
#		content.less		\
#		grids.less		\
#		libraries.less		\
#		pagination.less		\
#		itemScope.less		\
#		sprites.less		\
#		tabs.less		\
#		tagCloud.less		\
#		template.less		\
#		ui-form.less		\
#		themes/default/jquery-ui.css
#	lessc main.less connexions.css
#
#userItems.css: userItems.less colors.less sizes.less
#	lessc userItems.less userItems.css
