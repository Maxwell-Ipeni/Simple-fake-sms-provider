module.exports = {
  postcssOptions: {
    plugins: [
      // Tailwind was included previously; remove or add @tailwindcss/postcss
      // if you want Tailwind support. For now we only run autoprefixer so
      // the existing CSS builds without Tailwind plugin errors.
      require('autoprefixer'),
    ]
  }
}
