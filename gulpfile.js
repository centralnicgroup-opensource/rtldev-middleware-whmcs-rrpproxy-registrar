const { series, src, dest } = require('gulp')
const clean = require('gulp-clean')
const zip = require('gulp-zip')
const exec = require('util').promisify(require('child_process').exec)
const cfg = require('./gulpfile.json')

/**
 * Perform PHP Linting
 */
async function doLint()
{
  // these may fail, it's fine
    try {
        await exec(`${cfg.vendorbin}${cfg.phpcsfixcmd} ${cfg.phpcsparams}`)
    } catch (e) {
    }

  // these shouldn't fail
    try {
        await exec(`${cfg.vendorbin}${cfg.phpcschkcmd} ${cfg.phpcsparams}`)
        await exec(`${cfg.vendorbin}${cfg.phpcomptcmd} ${cfg.phpcsparams}`)
        await exec(`${cfg.vendorbin}${cfg.phpstancmd}`);
    } catch (e) {
        await Promise.reject(e.message)
    }
    await Promise.resolve()
}

/**
 * cleanup old build folder / archive
 * @return stream
 */
function doDistClean()
{
    return src([cfg.archiveBuildPath, `${cfg.archiveFileName} - latest.zip`], { read: false, base: '.', allowEmpty: true })
    .pipe(clean({ force: true }))
}

/**
 * Copy all files/folders to build folder
 * @return stream
 */
function doCopyFiles()
{
    return src(cfg.filesForArchive, { base: '.' })
    .pipe(dest(cfg.archiveBuildPath))
}

/**
 * Clean up files
 * @return stream
 */
function doFullClean()
{
    return src(cfg.filesForCleanup, { read: false, base: '.', allowEmpty: true })
    .pipe(clean({ force: true }))
}

/**
 * build latest zip archive
 * @return stream
 */
function doGitZip() {
  return src(`./${cfg.archiveBuildPath}/**`)
    .pipe(zip(`${cfg.archiveFileName}-latest.zip`))
    .pipe(dest('.'))
}

/**
 * build zip archive
 * @return stream
 */
function doZip() {
  return src(`./${cfg.archiveBuildPath}/**`)
    .pipe(zip(`${cfg.archiveFileName}.zip`))
    .pipe(dest('./pkg'))
}

exports.lint = series(
  doLint
)

exports.copy = series(
  doDistClean,
  doCopyFiles
)

exports.prepare = series(
  exports.lint,
  exports.copy
)

exports.archives = series(
  doGitZip,
  doZip
)

exports.default = series(
  exports.prepare,
  exports.archives,
  doFullClean
)
exports.release = series(
  exports.copy,
  exports.archives,
  doFullClean
)
