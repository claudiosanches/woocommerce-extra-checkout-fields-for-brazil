/*jshint node:true */
module.exports = function( grunt ) {
'use strict';

	grunt.initConfig({

		// Gets the package vars.
		pkg: grunt.file.readJSON( 'package.json' ),

		// Plugin directories.
		dirs: {
			admin: {
				js: 'assets/js/admin',
				css: 'assets/css/admin',
				images: 'assets/images/admin',
				fonts: 'assets/fonts/admin'
			},
			front: {
				js: 'assets/js/frontend',
				css: 'assets/css/frontend',
				images: 'assets/images/frontend',
				fonts: 'assets/fonts/frontend'
			}
		},

		// Javascript linting with jshint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.admin.js %>/*.js',
				'!<%= dirs.admin.js %>/*.min.js',
				'<%= dirs.front.js %>/*.js',
				'!<%= dirs.front.js %>/*.min.js'
			]
		},

		// Uglify to concat and minify.
		uglify: {
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.admin.js %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.admin.js %>',
					ext: '.min.js'
				}]
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.front.js %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.front.js %>',
					ext: '.min.js'
				}]
			}
		},

		// Process sass files.
		sass: {
			admin: {
				options: {
					sourcemap: 'none',
					style: 'compressed'
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.admin.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.admin.css %>/',
					ext: '.css'
				}]
            },
			frontend: {
				options: {
					sourcemap: 'none',
					style: 'compressed'
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.front.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.front.css %>/',
					ext: '.css'
				}]
			}
		},

		// Watch for changes and trigger compass, jshint and uglify.
		watch: {
			sass: {
				files: ['<%= dirs.admin.css %>/*.scss', '<%= dirs.front.css %>/*.scss'],
				tasks: ['sass']
			},
			js: {
				files: [
					'<%= jshint.all %>'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// Make .pot files.
		makepot: {
			dist: {
				options: {
					type: 'wp-plugin'
				}
			}
		},

		// Check text domain.
		checktextdomain: {
			options:{
				text_domain: '<%= pkg.name %>',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php', // Include all files.
					'!node_modules/**' // Exclude node_modules/
				],
				expand: true
			}
		},

		// Create README.md for GitHub.
		wp_readme_to_markdown: {
			options: {
				screenshot_url: 'http://ps.w.org/<%= pkg.name %>/assets/{screenshot}.png'
			},
			dest: {
				files: {
					'README.md': 'readme.txt'
				}
			}
		}
	});

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-imagemin' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );

	// Default task.
	grunt.registerTask( 'default', [
		'sass',
		'jshint',
		'uglify'
	] );

	grunt.registerTask( 'readme', 'wp_readme_to_markdown' );
};
