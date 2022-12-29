const chokidar = require('chokidar');

const paths = JSON.parse(process.argv[2]);

const watcher = chokidar.watch(paths, {
    ignoreInitial: true,
});

watcher
    .on('all', (event, path) => console.log(path));
