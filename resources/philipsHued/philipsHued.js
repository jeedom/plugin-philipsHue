/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
var Jeedom = require('./jeedom/jeedom.js');
var EventSource = require('eventsource');
const fs = require('fs');
const args = Jeedom.getArgs()

if(typeof args.loglevel == 'undefined'){
args.loglevel = 'debug';
}
Jeedom.log.setLevel(args.loglevel)
Jeedom.log.info('Start philipsHued')
Jeedom.log.info('Log level on  : '+args.loglevel)
Jeedom.log.info('PID file : '+args.pid)
Jeedom.log.info('Apikey : '+args.apikey)
Jeedom.log.info('Callback : '+args.callback)
Jeedom.log.info('Bridges : '+args.bridges)
Jeedom.write_pid(args.pid)
Jeedom.com.config(args.apikey,args.callback,0)
Jeedom.com.test()

var bridges = JSON.parse(args.bridges);

for (i in bridges) {
  let bridge = bridges[i]
  Jeedom.log.debug('Launch listenner for : '+bridge['ip']);
  let es = new EventSource('https://'+bridge['ip']+'/eventstream/clip/v2', {headers: {'hue-application-key': bridge['key']},https: {rejectUnauthorized: false}});
  es.onerror = function (err) {
    Jeedom.log.error('Error on connextion to bridge : '+err)
  };
  es.addEventListener('message', function (e) {
    Jeedom.log.debug('Event on bridge : '+i+' => '+e.data)
    Jeedom.com.add_changes('bridge::'+i,e.data);
  })
}


