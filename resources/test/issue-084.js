var assert = require('assert'),
browscap = require('../browscap.js'),
browser;

suite('checking for issue 084.', function () {
  test('issue-084-H', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (Linux; U) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/537.4");

    assert.strictEqual(browser['Browser'], 'Chrome');
    assert.strictEqual(browser['Browser_Type'], 'Browser');
    assert.strictEqual(browser['Browser_Bits'], '32');
    assert.strictEqual(browser['Browser_Maker'], 'Google Inc');
    assert.strictEqual(browser['Version'], '22.0');
    assert.strictEqual(browser['MajorVer'], '22');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'Linux');
    assert.strictEqual(browser['Platform_Version'], 'unknown');
    assert.strictEqual(browser['Platform_Bits'], '32');
    assert.strictEqual(browser['Platform_Maker'], 'Linux Foundation');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '');
    assert.strictEqual(browser['Device_Name'], 'Linux Desktop');
    assert.strictEqual(browser['Device_Maker'], 'Various');
    assert.strictEqual(browser['Device_Type'], 'Desktop');
    assert.strictEqual(browser['Device_Pointing_Method'], 'mouse');
    assert.strictEqual(browser['Device_Code_Name'], 'Linux Desktop');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'WebKit');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'Apple Inc');
  });
  test('issue-084-G', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (Linux; U; Android 4.0.4; pt-BR; H5000 Build/IMM76D) AppleWebKit/534.31 (KHTML, like Gecko) UCBrowser/9.3.0.321 U3/0.8.0 Mobile Safari/534.31");

    assert.strictEqual(browser['Browser'], 'UC Browser');
    assert.strictEqual(browser['Browser_Type'], 'Browser');
    assert.strictEqual(browser['Browser_Bits'], '32');
    assert.strictEqual(browser['Browser_Maker'], 'UC Web');
    assert.strictEqual(browser['Version'], '9.3');
    assert.strictEqual(browser['MajorVer'], '9');
    assert.strictEqual(browser['MinorVer'], '3');
    assert.strictEqual(browser['Platform'], 'Android');
    assert.strictEqual(browser['Platform_Version'], '4.0');
    assert.strictEqual(browser['Platform_Bits'], '32');
    assert.strictEqual(browser['Platform_Maker'], 'Google Inc');
    assert.strictEqual(browser['isMobileDevice'], '1');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '');
    assert.strictEqual(browser['Device_Name'], 'general Mobile Device');
    assert.strictEqual(browser['Device_Maker'], 'unknown');
    assert.strictEqual(browser['Device_Type'], 'Mobile Phone');
    assert.strictEqual(browser['Device_Pointing_Method'], 'touchscreen');
    assert.strictEqual(browser['Device_Code_Name'], 'general Mobile Device');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'U3');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'UC Web');
  });
  test('issue-084-F', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/5.0)");

    assert.strictEqual(browser['Browser'], 'Fake IE');
    assert.strictEqual(browser['Browser_Type'], 'Bot/Crawler');
    assert.strictEqual(browser['Browser_Bits'], '0');
    assert.strictEqual(browser['Browser_Maker'], 'unknown');
    assert.strictEqual(browser['Version'], '0.0');
    assert.strictEqual(browser['MajorVer'], '0');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'unknown');
    assert.strictEqual(browser['Platform_Version'], 'unknown');
    assert.strictEqual(browser['Platform_Bits'], '0');
    assert.strictEqual(browser['Platform_Maker'], 'unknown');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '1');
    assert.strictEqual(browser['Device_Name'], 'unknown');
    assert.strictEqual(browser['Device_Maker'], 'unknown');
    assert.strictEqual(browser['Device_Type'], 'unknown');
    assert.strictEqual(browser['Device_Pointing_Method'], 'unknown');
    assert.strictEqual(browser['Device_Code_Name'], 'unknown');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'unknown');
  });
  test('issue-084-E', function () {
    browser = browscap.getBrowser("Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; BTRS101607; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET4.0C; Ask");

    assert.strictEqual(browser['Browser'], 'IE');
    assert.strictEqual(browser['Browser_Type'], 'Browser');
    assert.strictEqual(browser['Browser_Bits'], '32');
    assert.strictEqual(browser['Browser_Maker'], 'Microsoft Corporation');
    assert.strictEqual(browser['Version'], '8.0');
    assert.strictEqual(browser['MajorVer'], '8');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'WinXP');
    assert.strictEqual(browser['Platform_Version'], '5.1');
    assert.strictEqual(browser['Platform_Bits'], '32');
    assert.strictEqual(browser['Platform_Maker'], 'Microsoft Corporation');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '');
    assert.strictEqual(browser['Device_Name'], 'Windows Desktop');
    assert.strictEqual(browser['Device_Maker'], 'Various');
    assert.strictEqual(browser['Device_Type'], 'Desktop');
    assert.strictEqual(browser['Device_Pointing_Method'], 'mouse');
    assert.strictEqual(browser['Device_Code_Name'], 'Windows Desktop');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'Trident');
    assert.strictEqual(browser['RenderingEngine_Version'], '4.0');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'Microsoft Corporation');
  });
  test('issue-084-D', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)");

    assert.strictEqual(browser['Browser'], 'Fake IE');
    assert.strictEqual(browser['Browser_Type'], 'Bot/Crawler');
    assert.strictEqual(browser['Browser_Bits'], '0');
    assert.strictEqual(browser['Browser_Maker'], 'unknown');
    assert.strictEqual(browser['Version'], '0.0');
    assert.strictEqual(browser['MajorVer'], '0');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'unknown');
    assert.strictEqual(browser['Platform_Version'], 'unknown');
    assert.strictEqual(browser['Platform_Bits'], '0');
    assert.strictEqual(browser['Platform_Maker'], 'unknown');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '1');
    assert.strictEqual(browser['Device_Name'], 'unknown');
    assert.strictEqual(browser['Device_Maker'], 'unknown');
    assert.strictEqual(browser['Device_Type'], 'unknown');
    assert.strictEqual(browser['Device_Pointing_Method'], 'unknown');
    assert.strictEqual(browser['Device_Code_Name'], 'unknown');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'unknown');
  });
  test('issue-084-C', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/4.0; InfoPath.2; SV1; .NET CLR 2.0.50727; WOW64)");

    assert.strictEqual(browser['Browser'], 'Fake IE');
    assert.strictEqual(browser['Browser_Type'], 'Bot/Crawler');
    assert.strictEqual(browser['Browser_Bits'], '0');
    assert.strictEqual(browser['Browser_Maker'], 'unknown');
    assert.strictEqual(browser['Version'], '0.0');
    assert.strictEqual(browser['MajorVer'], '0');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'unknown');
    assert.strictEqual(browser['Platform_Version'], 'unknown');
    assert.strictEqual(browser['Platform_Bits'], '0');
    assert.strictEqual(browser['Platform_Maker'], 'unknown');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '1');
    assert.strictEqual(browser['Device_Name'], 'unknown');
    assert.strictEqual(browser['Device_Maker'], 'unknown');
    assert.strictEqual(browser['Device_Type'], 'unknown');
    assert.strictEqual(browser['Device_Pointing_Method'], 'unknown');
    assert.strictEqual(browser['Device_Code_Name'], 'unknown');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'unknown');
  });
  test('issue-084-B', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (Linux; U; Android 4.2.2; es-sa; LG-D805 Build/JDQ39B) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.2 Mobile Safari/534.30");

    assert.strictEqual(browser['Browser'], 'Android');
    assert.strictEqual(browser['Browser_Type'], 'Browser');
    assert.strictEqual(browser['Browser_Bits'], '32');
    assert.strictEqual(browser['Browser_Maker'], 'Google Inc');
    assert.strictEqual(browser['Version'], '4.2');
    assert.strictEqual(browser['MajorVer'], '4');
    assert.strictEqual(browser['MinorVer'], '2');
    assert.strictEqual(browser['Platform'], 'Android');
    assert.strictEqual(browser['Platform_Version'], '4.2');
    assert.strictEqual(browser['Platform_Bits'], '32');
    assert.strictEqual(browser['Platform_Maker'], 'Google Inc');
    assert.strictEqual(browser['isMobileDevice'], '1');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '');
    assert.strictEqual(browser['Device_Name'], 'D805');
    assert.strictEqual(browser['Device_Maker'], 'LG');
    assert.strictEqual(browser['Device_Type'], 'Mobile Phone');
    assert.strictEqual(browser['Device_Pointing_Method'], 'touchscreen');
    assert.strictEqual(browser['Device_Code_Name'], 'D805');
    assert.strictEqual(browser['Device_Brand_Name'], 'LG');
    assert.strictEqual(browser['RenderingEngine_Name'], 'WebKit');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'Apple Inc');
  });
  test('issue-084-A', function () {
    browser = browscap.getBrowser("Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0");

    assert.strictEqual(browser['Browser'], 'Fake IE');
    assert.strictEqual(browser['Browser_Type'], 'Bot/Crawler');
    assert.strictEqual(browser['Browser_Bits'], '0');
    assert.strictEqual(browser['Browser_Maker'], 'unknown');
    assert.strictEqual(browser['Version'], '0.0');
    assert.strictEqual(browser['MajorVer'], '0');
    assert.strictEqual(browser['MinorVer'], '0');
    assert.strictEqual(browser['Platform'], 'unknown');
    assert.strictEqual(browser['Platform_Version'], 'unknown');
    assert.strictEqual(browser['Platform_Bits'], '0');
    assert.strictEqual(browser['Platform_Maker'], 'unknown');
    assert.strictEqual(browser['isMobileDevice'], '');
    assert.strictEqual(browser['isTablet'], '');
    assert.strictEqual(browser['Crawler'], '1');
    assert.strictEqual(browser['Device_Name'], 'unknown');
    assert.strictEqual(browser['Device_Maker'], 'unknown');
    assert.strictEqual(browser['Device_Type'], 'unknown');
    assert.strictEqual(browser['Device_Pointing_Method'], 'unknown');
    assert.strictEqual(browser['Device_Code_Name'], 'unknown');
    assert.strictEqual(browser['Device_Brand_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Name'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Version'], 'unknown');
    assert.strictEqual(browser['RenderingEngine_Maker'], 'unknown');
  });
});
