// Generated on 2013-12-20 using generator-angular 0.6.0
'use strict';

// # Globbing
// for performance reasons we're only matching one level down:
// 'test/spec/{,**/}*.js'
// use this if you want to recursively match all subfolders:
// 'test/spec/**/*.js'

module.exports = function(grunt) {

    // Load grunt tasks automatically
    require('load-grunt-tasks')(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    // Define the configuration for all the tasks
    grunt.initConfig({

        // Project settings
        ag: {
            vendor: 'vendor',
            css: 'zf-apigility/css',
            js: 'zf-apigility/js'
        },

        // Watches files for changes and runs tasks based on the changed files
        watch: {
            less: {
                files: ['<%= ag.css %>/main.less'],
                tasks: ['less:main']
            },
            gruntfile: {
                files: ['Gruntfile.js']
            },
            livereload: {
                options: {
                    livereload: true
                },
                files: [
                    '<%= ag.css %>/main.css'
                ]
            }
        },

        // Empties folders to start fresh
        clean: {
            dist: {
                files: [{
                    dot: true,
                    src: [
                        '.tmp',
                        '<%= ag.css %>/*.css',
                        '<%= ag.js %>/*.js'
                    ]
                }]
            },
            server: '.tmp'
        },

        // Compiles Less to CSS
        less: {
            main: {
                options: {
                    paths: [ '<%= ag.css %>/*.less' ],
                    cleancss: true
                },
                files: {
                    '<%= ag.css %>/main.min.css': '<%= ag.css %>/main.less'
                }
            }
        },

        // Copies remaining files to places other tasks can use
        copy: {
            js: {
                files: {
                    '<%= ag.js %>/jquery.min.js': '<%= ag.vendor %>/jquery/jquery.min.js',
                    '<%= ag.js %>/bootstrap.min.js': '<%= ag.vendor %>/bootstrap/dist/js/bootstrap.min.js'
                }
            },
            css: {
                files: {
                    '<%= ag.css %>/bootstrap.min.css': '<%= ag.vendor %>/bootstrap/dist/css/bootstrap.min.css'
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build', [
        'less:main',
        'copy:css',
        'copy:js'
    ]);

    grunt.registerTask('default', [
        'build'
    ]);
};
