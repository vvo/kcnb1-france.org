/* eslint-disable */

const cssnanoConfig = {
  preset: ["default", { discardComments: { removeAll: false } }],
};

module.exports = ({ file, options }) => {
  return {
    parser: options.enabled.optimize ? "postcss-safe-parser" : undefined,
    plugins: {
      autoprefixer: true,
      cssnano: options.enabled.optimize ? cssnanoConfig : false,
    },
  };
};
