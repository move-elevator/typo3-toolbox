<div align="center">

# TYPO3 extension `typo3_toolbox`

[![Supported TYPO3 versions](https://badgen.net/badge/TYPO3/13/orange)]()
[![License](https://poser.pugx.org/move-elevator/typo3-toolbox/license)](LICENSE.md)
[![last commit](https://img.shields.io/github/last-commit/move-elevator/typo3-toolbox)](https://github.com/move-elevator/typo3-toolbox/commits)

</div>

This extension provides several tools for TYPO3 integrators and developers.

## Features:
- Adds an event listener to minify HTML output
- Adds an event listener to add save & close button
- Adds a xClass for TYPO3 asset collector which will automatically render `noscript` tags beside CSS link tags, which can be adopted to optimize CSS preloading (see: https://web.dev/articles/defer-non-critical-css)
- Adds a view helper which can return the uid of the first content element on a page X
