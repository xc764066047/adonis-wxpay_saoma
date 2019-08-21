'use strict'
//yarn add log4js
const logger       = use('App/Services/Logger')
const Config       = use('Config')
const moment       = use('moment') //yarn add moment
const randomString = use('randomstring') 
const queryString  = use('querystring') //将对象转成URL
const crypto       = use('crypto')
const convert      = use('xml-js') //yarn add xml-js
const axios        = use('axios') //yarn add axios 
const qrcode       = use('qrcode') //yarn add qrcode 

class CheckoutController {
  wxPaySign(data, key) {
    // 1.排序
    const sortedOrder = Object.keys(data).sort().reduce((accumulator, key) => {
      accumulator[key] = data[key]
      logger.info(accumulator)
      // appid,mch_id,out_trade_no,body,...对他们进行排序
      return accumulator
    }, {})

    // 2.转化为地址查询符
    //上面排序完成以后 拼接成URL
    const stringOrder = queryString.stringify(sortedOrder, null, null, {
      encodeURIComponent: queryString.unescape
    })

    // 3.结尾加上秘钥
    //https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=4_3 官方签名文档中要求我们加上key
    const stringOrderWithKey = `${ stringOrder }&key=${ key }` 
    //结尾拼接上key

    // 4.md5 后全部大写
    const sign = crypto.createHash('md5').update(stringOrderWithKey).digest('hex').toUpperCase()

    return sign
  }

  async render({ view }) {
    // 公众账号 ID
    const appid = Config.get('wxpay.appid')

    // 商户号
    const mch_id = Config.get('wxpay.mch_id')

    // 秘钥
    const key = Config.get('wxpay.key')
    //大小写字母 0-9 用私钥生成器 生成   


    // 商户订单号
    const out_trade_no = moment().local().format('YYYYMMDDHHmmss')

    // 商品描述
    const body = 'felixlu'

    // 商品价格
    const total_fee = 1

    // 支付类型
    const trade_type = 'NATIVE'

    // 商品ID（如果支付类型为NATIVE, 商品ID必填）
    const product_id = 1

    // 通知地址
    const notify_url = Config.get('wxpay.notify_url')

    // 随机字符
    const nonce_str = randomString.generate(32)

    // 统一下单接口
    const unifiedOrderApi = Config.get('wxpay.api.unifiedorder')

    let order = {
      appid,
      mch_id,
      out_trade_no,
      body,
      trade_type,
      total_fee,
      product_id,
      notify_url,
      nonce_str
    }

    const sign = this.wxPaySign(order, key)

    order = {
      xml: {
        ...order,
        sign
      }
    }

    // 转换成xml
    const xmlOrder = convert.js2xml(order, {
      compact: true
    })

    const wxPayResponse = await axios.post(unifiedOrderApi, xmlOrder)  //返回内容  
    //返回的内容是xml  内容中有  code_url  
    //接下来生成二维码   
    //取出内容中的 code_url  需要通过dom的方式拿出来
    //既然用dom 需要将 xml转成js    

    const _prepay = convert.xml2js(wxPayResponse.data, {
      compact: true,
      cdataKey: 'value',
      textKey: 'value'
    }).xml
   

    const prepay = Object.keys(_prepay).reduce((accumulator, key) => {
      accumulator[key] = _prepay[key].value
      return accumulator 
    }, {})  //这一步 就是为了 取出 code_url 

    // 生成二维码链接 拿到code_url 
    //pip install qrcode 
    const qrcodeUrl = await qrcode.toDataURL(prepay.code_url, { width: 300 })
    //返回的是base64编码 载页面上直接可以显示图片的  
    //base64 可以直接转成图片


    //将qrcodeUrl渲染到页面上 
    return view.render('commerce.checkout', { qrcodeUrl })
  }

  wxPayNotify({ request }) {
    const _payment = convert.xml2js(request._raw, {
      compact: true,
      cdataKey: 'value',
      textKey: 'value'
    }).xml
    //微信调用回调函数 回返回一个签名 xml 类型  需要将签名 拿出来 需要转成js  
    //
    const payment = convert.keys(_payment).reduce((accumulator, key) => {
      accumulator[key] = _payment[key].value
      return accumulator
    })

    const paymentSign = payment.sign  //这就是拿出来的签名 
    //微信返回的签名   

    delete payment['sign']

    const key = Config.get('wxpay.key') //拿着我自己的key 

    const selfSign = this.wxPaySign(payment, key)//重新调用 生成签名的方法   


    const return_code = paymentSign === selfSign ? 'SUCCESS' : 'FAIL'  //然后进行对比   

    const reply = {
      xml: {
        return_code  
      }
    }

    return convert.js2xml(reply, {
      compact: true    //还要返回给 微信  成功还是失败   
      //这样才是真正的结束了  
    })
  }
}

module.exports = CheckoutController
