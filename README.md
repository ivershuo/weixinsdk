### 微信公众平台自动回复SDK

配置好token去http://mp.weixin.qq.com/ 填上

new WeixinMP会自动处理授权验证过程

调用response()方法会按规则返回信息
(规则可以在规则目录配置或者继承自己写相应do*方法或者在getPostData()方法中拿到自己处理)

p.s: 因为在BAE测试，BAE是php 5.2版本滴，支持php 5.2.