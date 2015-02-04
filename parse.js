var 
  system = require('system'),
  model, output, size;
var handler = system.args[1];

if (system.args.length < 3)
  phantom.exit();

file = system.args[1];
url = system.args[2];

var page = require('webpage').create();
page.settings.resourceTimeout = 5000; // 15 seconds
page.viewportSize = {
  width: 1366,
  height: 768
};
page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36';
page.onResourceTimeout = function(e) {
  console.log('timeout');
  phantom.exit(1);
};

phantom.addCookie({
  'name'     : 'yandex_gid',   /* required property */
  'value'    : 2,  /* required property */
  'domain'   : 'market.yandex.ru',
  'path'     : '/',                /* required property */
  'expires'  : (new Date()).getTime() + (1000 * 60 * 60)   /* <-- expires in 1 hour */
});

var fs = require('fs');
var CookieJar = "/tmp/cookiejar.json";
var pageResponses = [];
page.onResourceReceived = function(response) {
    pageResponses[response.url] = response;
    fs.write(CookieJar, JSON.stringify(phantom.cookies), "w");
};
if(fs.isFile(CookieJar))
    Array.prototype.forEach.call(JSON.parse(fs.read(CookieJar)), function(x){
        phantom.addCookie(x);
    });


function ExtractFromPage( url, extract, cb )
{
  page.open(url, function (status) 
  {
    if (status !== 'success')
    {
      console.log('LOAD_FAIL');
      console.log(page.reason);
      console.log(page.reason_url);
      console.log(status);
      phantom.exit();
    }
    else 
    {
      //console.log(url);

      page.includeJs("http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js", 
        function() 
        {
          var res = page.evaluate(extract);
          cb(res);
        });
      //var res = page.evaluate(extract);
      //cb(res);
    }
  });  
}

var ret = {};

var exportf = function()
{
  ExtractFromPage(url,
    function()
    { // Inside browser

      parsed = {};
      parsed['name'] = $('.document-title[itemprop="name"]').text();
      parsed['rating'] = $('.rating-box [itemprop="ratingValue"]').attr('content');
      parsed['rated'] = $('.rating-box [itemprop="ratingCount"]').attr('content');
      parsed['installs'] = $('[itemprop="numDownloads"]').text();
      parsed['updated'] = $('[itemprop="datePublished"]').text();
      parsed['category'] = $('[itemprop="genre"]').text();
      parsed['urls'] = [];
      $('.cards a.title').each(function()
      {
        parsed['urls'].push($(this).attr('href'));
      })

      return {'parsed': parsed};
    }, // Outside browser
    function (parsed_data)
    {
      for (var k in parsed_data)
        ret[k] = parsed_data[k];
      Exit();
    })
}

function Exit()
{
  ret.body = page.content;
  //ret.shot = page.renderBase64('JPEG');
  for (var k in pageResponses)
    ret.headers = {} || pageResponses[k].headers;
  ret.url = url;

  var fs = require('fs');
  fs.write(file, JSON.stringify(ret), 'w');  
  phantom.exit();
};

exportf();
