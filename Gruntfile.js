/*jshint node:true */
var expandHomeDir = require( 'expand-home-dir' );

module.exports = function( grunt ) {
'use strict';

	grunt.initConfig({

		// gets the package vars
		pkg: grunt.file.readJSON( 'package.json' ),

		// plugin directories
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

		// svn settings
		svn_settings: {
			path: expandHomeDir( '~/Projects/wordpress-plugins-svn/' ) + '<%= pkg.name %>',
			tag: '<%= svn_settings.path %>/tags/<%= pkg.version %>',
			trunk: '<%= svn_settings.path %>/trunk',
			exclude: [
				'.git/',
				'.sass-cache/',
				'assets/css/admin/*.scss',
				'node_modules/',
				'.editorconfig',
				'.gitignore',
				'.jshintrc',
				'Gruntfile.js',
				'README.md',
				'package.json',
				'*.zip'
			]
		},

		// javascript linting with jshint
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

		// uglify to concat and minify
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

		// process sass files
		sass: {
			compile: {
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
			}
		},

		// watch for changes and trigger compass, jshint and uglify
		watch: {
			sass: {
				files: ['<%= dirs.admin.css %>/*.scss'],
				tasks: ['sass']
			},
			js: {
				files: [
					'<%= jshint.all %>'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// image optimization
		imagemin: {
			dist: {
				options: {
					optimizationLevel: 7,
					progressive: true
				},
				files: [
					{
						expand: true,
						cwd: '<%= dirs.admin.images %>/',
						src: '**/*.{png,jpg,gif}',
						dest: '<%= dirs.admin.images %>/'
					},
					{
						expand: true,
						cwd: '<%= dirs.front.images %>/',
						src: '**/*.{png,jpg,gif}',
						dest: '<%= dirs.front.images %>/'
					},
					{
						expand: true,
						cwd: './',
						src: 'screenshot-*.png',
						dest: './'
					}
				]
			}
		},

		// rsync commands used to take the files to svn repository
		rsync: {
			options: {
				args: ['--verbose'],
				exclude: '<%= svn_settings.exclude %>',
				syncDest: true,
				recursive: true
			},
			tag: {
				options: {
					src: './',
					dest: '<%= svn_settings.tag %>'
				}
			},
			trunk: {
				options: {
				src: './',
				dest: '<%= svn_settings.trunk %>'
				}
			}
		},

		// Make .pot files
		makepot: {
			dist: {
				options: {
					type: 'wp-plugin'
				}
			}
		},

		// Check text domain
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
					'**/*.php', // Include all files
					'!node_modules/**' // Exclude node_modules/
				],
				expand: true
			}
		},

		// shell command to commit the new version of the plugin
		shell: {
			// Remove delete files.
			svn_remove: {
				command: 'svn st | grep \'^!\' | awk \'{print $2}\' | xargs svn --force delete',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Add new files.
			svn_add: {
				command: 'svn add --force * --auto-props --parents --depth infinity -q',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Commit the changes.
			svn_commit: {
				command: 'svn commit -m "updated the plugin version to <%= pkg.version %>"',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-imagemin' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-rsync' );
	grunt.loadNpmTasks( 'grunt-shell' );

	// default task
	grunt.registerTask( 'default', [
		'sass',
		'jshint',
		'uglify'
	] );

	// deploy task
	grunt.registerTask( 'deploy', [
		'default',
		'rsync:tag',
		'rsync:trunk',
		'shell:svn_remove',
		'shell:svn_add',
		'shell:svn_commit'
	] );
};
