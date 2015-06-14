/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
//import org.apache.commons.lang.NumberUtils;
import org.eclipse.paho.client.mqttv3.IMqttDeliveryToken;
import org.eclipse.paho.client.mqttv3.MqttCallback;
import org.eclipse.paho.client.mqttv3.MqttClient;
import org.eclipse.paho.client.mqttv3.MqttException;
import org.eclipse.paho.client.mqttv3.MqttMessage;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

import java.util.TimerTask;
import java.util.Timer;

import org.json.*;

import java.io.ByteArrayOutputStream;
import java.io.PrintStream;

/**
 *
 * @author Fauziah Rahmawati
 */
public class Mulai{
	public static void main(String[] args) {
       //System.out.println("user ID: "+args[0]);
       //System.out.println("things ID: "+args[1]);
       //new Gateway(args[0],args[1]);

       Timer timer = new Timer();
       timer.schedule(new Gateway(), 0, 10000);
	}

	static class Gateway extends TimerTask implements MqttCallback{
    
    public MqttClient clientPub;
    public MqttClient clientSub;
    public MqttMessage message;
    public Thread mqttThread;
    public String userID;

    static Connection con = null;
    static Statement st = null;
    static ResultSet rs = null;

    String url = "jdbc:mysql://localhost:3306/gateway";
    String user = "root";
    String password = "root";
    
    String apiKey = "LUQMANGWTA";

    public Gateway(){
    	//mengambil user id
        this.userID = getUserId();
        try {
            // membuat client sebagai publisher
            clientPub = new MqttClient("tcp://128.199.236.53:1883", "Publisher");
            System.out.println("Publisher created");
            // membuat client sebagai subscriber
            clientSub = new MqttClient("tcp://128.199.236.53:1883", "Subscriber");
            System.out.println("Subscriber created");
            
            connectMosquitto(clientSub);
            connectMosquitto(clientPub);
            
        } catch (MqttException ex) {
            ex.printStackTrace();
        }
        try{
            con = DriverManager.getConnection(url, user, password);
            st = con.createStatement();
        }catch(Exception e){
            System.out.println("eror konek ke database");
        }
    }
    public void run() {

       if (clientPub.isConnected()) {

       		rs = doQuery("Select * from things");
       		try{
	       		while(rs.next()){
	       			String id = rs.getString(1);
	       			String localId = rs.getString(4);
                    if (rs.getString(6).length() > 0) {
                        String access[] = rs.getString(6).split(",");
                        String url = "http://localhost:8080/api/"+apiKey+"/lights/"+localId;
                        String jsonString = executeREST("GET", url, null);
                        if (jsonString != null && jsonString.charAt(0) != '[') {
                            //System.out.println("masih masuk sini: "+jsonString);
                            JSONObject jsonObject = new JSONObject(jsonString);
                            JSONObject newJSON = jsonObject.getJSONObject("state");
                            //System.out.println(newJSON.toString());
                            jsonObject = new JSONObject(newJSON.toString());

                            for (String attr : access) {
                                if (attr.equals("on")) {
                                    publish("sot/"+userID+"/undefined/"+id+"/"+attr+"/acc", attr+": "+(jsonObject.getBoolean(attr) == true ? "true" : "false" ));
                                    System.out.println(attr+" lampu ke "+localId+" : "+(jsonObject.getBoolean(attr) == true ? "true" : "false" ));
                                }else{
                                    publish("sot/"+userID+"/undefined/"+id+"/"+attr+"/acc", attr+": "+jsonObject.getInt(attr));
                                    System.out.println(attr+" lampu ke "+localId+" : "+jsonObject.getInt(attr));
                                }
                                
                            }
                        }
                    }
	       			
	            }
       		}catch(Exception e){
       			ByteArrayOutputStream baos = new ByteArrayOutputStream();
       			    PrintStream ps = new PrintStream(baos);
       			    e.printStackTrace(ps);
       			    ps.close();
       			    System.out.println(baos.toString());
       			System.out.println("Error di run");
       		}
            
        } 
    }
    /**
     * Method untuk menghubungkan client dengan broker
     * @param c merupakan client yang akan dihubungkan ke broker
     */
    private void connectMosquitto(final MqttClient c){
        try {
            // menghubungkan client dengan broker
            c.connect();
            c.setCallback(this);
            if(c.getClientId().equalsIgnoreCase("Subscriber")){
                // client diatur agar bisa menerima pesan dari broker
                c.setCallback(Gateway.this);
                String topik = "sot/g/"+userID+"/+/+/+/ctl";
                System.out.println(topik);
                c.subscribe(topik);
                System.out.println("Subscriber connected");
            }
            else{
                System.out.println("Publisher connected");
            }
        }
        catch (MqttException ex) {
            ex.printStackTrace();
        }
    }
    
    /**
     * Method untuk mengirimkan pesan ke broker
     * @param topic merupakan nama topik dari pesan yang akan dikirim
     * @param msg merupakan isi pesan yang akan dikirim
     */
    private void publish(String topic, String msg){
        try {
            // membuat sebuah pesan MQTT
            message = new MqttMessage();
            // mengatur payload dari message menjadi array of byte dari pesan yang dikirim oleh ZigBee coordinator
            message.setPayload(msg.getBytes());
            // mem-publish pesan ke MQTT broker
            clientPub.publish(topic, message);
            System.out.println("Client publish: " + topic);
        }
        catch (MqttException ex) {
            ex.printStackTrace();
        }
    }

    /**
     * Method yang dipanggil jika koneksi terputus
     * @param thrwbl alasan yang menyebabkan koneksi terputus
     */
    @Override
    public void connectionLost(Throwable thrwbl) {
        System.out.println("Connection lost");
        System.out.println(thrwbl);
    }

    /**
     * Method yang dipanggil jika ada pesan dari broker
     * @param topic merupakan nama topik dari pesan yang dikirim oleh broker
     * @param msg merupakan isi pesan yang dikirim oleh broker
     */
    @Override
    public void messageArrived(String topic, MqttMessage msg){
        System.out.println("Topic : " + topic + ", message : " + msg);
        String command = msg.toString();
        // mengecek isi pesan dari broker
        checkCommand(topic, command);
    }

    @Override
    public void deliveryComplete(IMqttDeliveryToken imdt) {
        System.out.println("Delivery complete");
    }
    
    /**
     * Method yang digunakan untuk menjalankan perintah dari broker
     * @param topic merupakan nama topik dari pesan yang dikirim broker
     * @param command merupakan isi pesan dari broker
     */
    void checkCommand(String topic, String command){
        String msg = "";
        //sot/g/userid/categoryname/thingid/propertyname/acc or ctl
        //String wrong = "Wrong command";
        String[] topicArr = topic.split("/");
        String localId = getLocalId(topicArr[4]);
        String tipe = getTipe(topicArr[4]);
        String attr = topicArr[5];
        String allowed[] = getAttr(topicArr[4]).split(","); 
        String url = "";
        if (arrayContain(allowed,attr)) {
            if (tipe.equals("Lampu")) {
                url = "http://localhost:8080/api/"+apiKey+"/lights/"+localId+"/state";
            }else{
                url = "http://localhost:8080/api/"+apiKey+"/groups/"+localId+"/action";
            }
            //int length = topicArr.length;
                    
            if (Integer.parseInt(localId) > 0) {

                msg = executeREST("PUT", url, "{\""+attr+"\":"+command+"}");
            }else{
                //publish("sot/"+userID+"/ledlight/"+thingsID+"/log", "ID things tidak ditemukan");
                System.out.println("id lampu tidak ditemukan");
            }
        }else{
            System.out.println("atribut tidak diperbolehkan untuk di kontrol");
        }
    }
    
    /**
     * Method untuk mengirimkan perintah REST ke REST API
     * @param method merupakan jenis method yang digunakan
     * @param targetURL merupakan alamat URI dari server REST
     * @param parameter merupakan parameter dari perintah REST
     * @return mengembalikan respon dari REST dalam bentuk String
     */
    private String executeREST(String method, String targetURL, String parameter){
        URL url;
        HttpURLConnection connection = null;
        try {
            // membuat alamat URI yang dituju
            url = new URL(targetURL);
            // membuat koneksi ke URL
            connection = (HttpURLConnection)url.openConnection();
            // mengatur method sesuai permintaan dari gateway
            connection.setRequestMethod(method);
            connection.setUseCaches(false);
            connection.setDoInput(true);
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/json");
        connection.setRequestProperty("Accept", "application/json");
            connection.setRequestProperty("Content-Language", "en-US");
            
            // menuliskan isi perintah
            if(parameter != null){
                // mengatur parameter sebagai request property
                /*connection.setRequestProperty("Content-Length", "" + Integer.toString(parameter.getBytes().length));
                DataOutputStream wr = new DataOutputStream (connection.getOutputStream());*/
                 connection.setRequestProperty("Accept", "application/json");
        OutputStreamWriter osw = new OutputStreamWriter(connection.getOutputStream());
                // mengirim request
                //wr.write(parameter.getBytes());
        		osw.write(parameter);
                osw.flush ();
                osw.close ();
            }
            
            // menghubungkan ke server
            connection.connect();
            // membaca respon
            InputStream is = connection.getInputStream();
            BufferedReader rd = new BufferedReader(new InputStreamReader(is));
            String line;
            StringBuffer response = new StringBuffer();
            // membaca respon perbaris
            while((line = rd.readLine()) != null){
                response.append(line);
                response.append('\r');
            }
            rd.close();
            // jika koneksi publisher terputus, akan melakukan koneksi ulang
            if(!clientPub.isConnected()){
                connectMosquitto(clientPub);
            }
            // jika koneksi subscriber terputus, akan melakukan koneksi ulang
            if(!clientSub.isConnected()){
                connectMosquitto(clientSub);
            }
            String resp = response.toString();
            // mengirimkan respon ke pemanggil
            return resp;
        }
        catch(Exception e){
                //e.printStackTrace();
                return null;
        }
        finally{
            if(connection != null){
                connection.disconnect(); 
            }
        }
    }

    private boolean arrayContain(String[] haystack, String needle){
        for (String s : haystack) {
            if (s.equals(needle)) {
                return true;
            }
        }
        return false;
    }
    private String getLocalId(String id){
        try{
            rs = doQuery("Select * from things");
            //System.out.println("Sukses executeQuery");
            boolean notFound = true;
            while(rs.next() && notFound){
                System.out.println("Di dalam while");
                if (id.equals(rs.getString(1))) {
                    System.out.println("ketemu");
                    return rs.getString(4);
                }
            }
        }catch(Exception e){
            ByteArrayOutputStream baos = new ByteArrayOutputStream();
                PrintStream ps = new PrintStream(baos);
                e.printStackTrace(ps);
                ps.close();
                System.out.println(baos.toString());
            System.out.println("Error ketika get local id: "+id);
            return "-1";
        }

        return "-1";
    }

    private String getTipe(String id){
        try{
            rs = doQuery("Select * from things");
            //System.out.println("Sukses executeQuery");
            boolean notFound = true;
            while(rs.next() && notFound){
                System.out.println("Di dalam while");
                if (id.equals(rs.getString(1))) {
                    System.out.println("ketemu");
                    return rs.getString(2);
                }
            }
        }catch(Exception e){
            ByteArrayOutputStream baos = new ByteArrayOutputStream();
                PrintStream ps = new PrintStream(baos);
                e.printStackTrace(ps);
                ps.close();
                System.out.println(baos.toString());
            System.out.println("Error ketika get tipe: "+id);
            return "-1";
        }

        return "-1";
    }

    private String getUserId(){
    	try{
            rs = doQuery("Select * from setting where kunci = 'user_id'");
            //System.out.println("Sukses executeQuery");
            boolean notFound = true;
            while(rs.next() && notFound){
                return rs.getString(2);
            }
        }catch(Exception e){
            ByteArrayOutputStream baos = new ByteArrayOutputStream();
                PrintStream ps = new PrintStream(baos);
                e.printStackTrace(ps);
                ps.close();
                System.out.println(baos.toString());
            System.out.println("Error ketika get user id: ");
            return "-1";
        }

        return "-1";
    }

    private String getAttr(String id){
        try{
            rs = doQuery("Select * from things");
            //System.out.println("Sukses executeQuery");
            boolean notFound = true;
            while(rs.next() && notFound){
                System.out.println("Di dalam while");
                if (id.equals(rs.getString(1))) {
                    System.out.println("ketemu");
                    return rs.getString(5);
                }
            }
        }catch(Exception e){
            ByteArrayOutputStream baos = new ByteArrayOutputStream();
                PrintStream ps = new PrintStream(baos);
                e.printStackTrace(ps);
                ps.close();
                System.out.println(baos.toString());
            System.out.println("Error ketika get tipe: "+id);
            return "-1";
        }

        return "-1";
    }

    private ResultSet doQuery(String query){
    	try{
    		if (st == null) {
    		    System.out.println("st nya null");
    		    con = DriverManager.getConnection(url, user, password);
    		    st = con.createStatement();
    		}
    		return st.executeQuery(query);
    	}catch(Exception e){
    		System.out.println("error ketika query");
    		return null;
    	}
    }
}
}


