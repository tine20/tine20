const path = require('path');
const baseDir = path.resolve(__dirname , '../../');
/**
 * moves unnamed chunks into "<app>/js/" dir
 * with this a chunkFilename in import() / require.ensure() calls is not longer needed
 */
class ChunkNamePlugin {
    apply(compiler) {
        compiler.hooks.compilation.tap(
            "ChunkNamePlugin",
            (compilation, { normalModuleFactory }) => {
                compilation.chunkTemplate.hooks.renderManifest.tap(
                    "ChunkNamePlugin",
                    (result, options) => {
                        const chunk = options.chunk;
                        const outputOptions = options.outputOptions;

                        if (! chunk.name) {
                            let app = 'Tinebase'
                            chunk.getModules().forEach((module) => {
                                const relPath = module.resource.replace(baseDir, '');
                                const moduleApp = (relPath.split('/')[1]);
                                // @TODO - common chunks should be factored out
                                // @TODO - what about mixed app chunks -> should not happen with common chunks
                                if (moduleApp !== 'Tinebase') {
                                    app = moduleApp;
                                }
                            });
                            chunk.name = `${app}/js/${chunk.id}`;
                        }
                    }
                );
            }
        );
    }
}

module.exports = ChunkNamePlugin;
