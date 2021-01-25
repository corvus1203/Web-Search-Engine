import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.io.File;
import java.io.FileWriter;
import java.io.PrintWriter;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Scanner;
import java.util.Set;

public class ExtractLinks {
	
	public static void main(String[] args) throws Exception{
		
		HashMap<String, String> fileUrlMap = new HashMap<String, String>();
		HashMap<String, String> urlFileMap = new HashMap<String, String>();
		
		File mapfile = new File("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/NYTIMES/URLtoHTML_nytimes_news.csv");
	    Scanner myReader = new Scanner(mapfile);
	    while (myReader.hasNextLine()) {
	    	String line = myReader.nextLine();
	    	String str[] = line.split(",");
	    	urlFileMap.put(str[1], str[0]);
	    	fileUrlMap.put(str[0], str[1]);
	    }
	    myReader.close();
	    System.out.println("maps generated");
	    
	    PrintWriter writer = new PrintWriter("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/edgelist.txt");
		File dir = new File("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/NYTIMES/nytimes");
		Set<String> edges = new HashSet<String>();
		int i = 0;
		for (File file: dir.listFiles()) {
			System.out.println(i++);
			Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
			Elements links = doc.select("a[href]");
			Elements pngs = doc.select("[src]");
			
			for (Element link: links) {
				String url = link.attr("abs:href").trim();
				if (urlFileMap.containsKey(url)) {
					edges.add(file.getName() + " " + urlFileMap.get(url));
				}
			}
		}
		
		for(String s: edges) {
			writer.println(s);
		}
		
		System.out.println("done!");
		writer.flush();
		writer.close();
		
	}

}
