phpRegression is a fast and simple linear regression library.

The project includes a demo which downloads stock price history, performs a linear regression on the data, and generates an image showing the calculated trend line versus the actual stock prices.

To get started, run:
    php linearRegressionDemo.php aapl
    php linearRegressionDemo.php googl

This will do a five-year analysis on Apple Inc.  The resulting output will be a png file in the current directory.  To see the results, open the file with an image viewer or web browser.

You can control which stocks to analyze, as well as the date range.  Running this with no other arguments will give you help:
    php linearRegressionDemo.php
