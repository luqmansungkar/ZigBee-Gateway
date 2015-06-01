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

/**
 *
 * @author Fauziah Rahmawati
 */
public class Mulai{
	public static void main(String[] args) {
       System.out.println("user ID: "+args[0]);
       System.out.println("things ID: "+args[1]);
       new Gateway(args[0],args[1]);
	}

	static class Gateway implements MqttCallback{
    
    public MqttClient clientPub;
    public MqttClient clientSub;
    public MqttMessage message;
    public Thread mqttThread;
    public String userID;
    public String thingsID;

    Connection con = null;
    Statement st = null;
    ResultSet rs = null;

    String url = "jdbc:mysql://localhost:3306/gateway";
    String user = "root";
    String password = "root";
    
    String apiKey = "LUQMANGWTA";

    public Gateway(String userID, String thingsID){
        this.userID = userID;
        this.thingsID = thingsID;
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
    
    /**
     * Method untuk menghubungkan client dengan broker
     * @param c merupakan client yang akan dihubungkan ke broker
     */
    private void connectMosquitto(final MqttClient c){
        try {
            // menghubungkan client dengan broker
            c.connect();
            c.setCallback(this);
            // jika client adalah subscriber diatur agar subscribe ke topik tentang jaringan ZigBee
            if(c.getClientId().equalsIgnoreCase("Subscriber")){
                // client diatur agar bisa menerima pesan dari broker
                c.setCallback(Gateway.this);
                // subscribe info tentang coordinator
                //c.subscribe("coordinator/#");
                // subscribe info tentang lampu
                //c.subscribe("lights/#");
                // subscribe info tentang grup dan scene
                //c.subscribe("groups/#");
                //String topik = "sot/"+userID+"/ledlight/"+thingsID+"/";
                //sot/g/userid/categoryname/thingid/propertyname/acc or ctl
                String topik = "sot/g/"+userID+"/+/+/+/ctl";
                System.out.println(topik);
                c.subscribe(topik);
                System.out.println("Subscriber connected");
            }
            else{
            	//publish("yeah/lights","nyala euy");
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
        String attr = topicArr[5];
        String url = "http://localhost:8080/api/"+apiKey+"/lights/"+localId+"/state";
        //int length = topicArr.length;
                
        if (Integer.parseInt(localId) > 0) {

            msg = executeREST("PUT", url, "{\""+attr+"\":"+command+"}");
            /*if (command.equalsIgnoreCase("status")) {
                msg = executeREST("PUT", "http://localhost:8080/api/4171700133/lights/1/state", "{'on':true}");
                msg = executeREST("GET", "http://localhost:8080/api/4171700133/lights/"+localId, null);
            }else if (command.equalsIgnoreCase("nyala")) {
                msg = executeREST("PUT", "http://localhost:8080/api/4171700133/lights/"+localId+"/state", "{\"on\":true}");
            }else if (command.equalsIgnoreCase("mati")) {
                msg = executeREST("PUT", "http://localhost:8080/api/4171700133/lights/"+localId+"/state", "{\"on\":false}");
            }
            */
            // mengirimkan pesan dan topik ke broker
            //publish("sot/"+userID+"/ledlight/"+thingsID+"/log", msg);
        }else{
            publish("sot/"+userID+"/ledlight/"+thingsID+"/log", "ID things tidak ditemukan");
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
                e.printStackTrace();
                return null;
        }
        finally{
            if(connection != null){
                connection.disconnect(); 
            }
        }
    }

    private String getLocalId(String id){
        try{
            rs = st.executeQuery("Select * from things");
            boolean notFound = true;
            while(rs.next() && notFound){
                if (id.equals(rs.getString(1))) {
                    return rs.getString(4);
                }
            }
        }catch(Exception e){
            System.out.println("Error ketika get local id");
            return "-1";
        }

        return "-1";
    }
}
}


